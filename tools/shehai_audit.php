<?php

/**
 * 文件作用：只读遍历1440课基线输入，对比涉害深度与复等判定假设。
 *
 * 此工具不修改 PanCalculator，不写入回归夹具，不作为基线批准依据。
 */

use App\Services\PanCalculator;
use App\Support\PanCreationData;
use App\Support\PanRegression;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__).'/vendor/autoload.php';

$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$calculator = $app->make(PanCalculator::class);
$fixture = PanRegression::loadFixture();
$summaryOnly = in_array('--summary', $argv, true);

$methods = ['shehai', 'shehai_jianji', 'shehai_chawei', 'shehai_zhuixia'];
$counts = [
    'current' => array_fill_keys($methods, 0),
    'current_depth_same_rank_fudeng' => array_fill_keys($methods, 0),
    'open_interval_depth_same_rank_fudeng' => array_fill_keys($methods, 0),
    'kejIng_open_interval' => array_fill_keys($methods, 0),
];
$changes = [
    'current_depth_same_rank_fudeng' => [],
    'open_interval_depth_same_rank_fudeng' => [],
    'kejIng_open_interval' => [],
];
$baselineImpact = [
    'scoped_current_depth_fudeng' => newImpactSummary(),
    'dinge_open_interval' => newImpactSummary(),
    'kejing_open_interval' => newImpactSummary(),
];

foreach ($fixture['cases'] as $caseId => $case) {
    $pan = $calculator->calculate($case['input'])->toArray();
    $trace = $pan['shehaiTrace'] ?? null;

    if (! is_array($trace) || ($trace['candidates'] ?? []) === []) {
        continue;
    }

    $currentMethod = $pan['calculationTrace']['initial_transmission']['method'] ?? null;

    if (! in_array($currentMethod, $methods, true)) {
        continue;
    }

    $currentDepths = array_map(
        static fn (array $candidate): int => $candidate['depth'],
        $trace['candidates'],
    );
    $openIntervalDepths = array_map(
        static fn (array $candidate): int => openIntervalDepth($candidate, $trace['relation']),
        $trace['candidates'],
    );
    $currentDepthSameRankFudeng = classify($trace['candidates'], $currentDepths);
    $openIntervalDepthSameRankFudeng = classify($trace['candidates'], $openIntervalDepths);
    $kejIngOpenInterval = classifyKejIng($trace['candidates'], $openIntervalDepths);
    $dingeEvaluation = evaluateDinge($pan, $trace['candidates'], $openIntervalDepths);
    $kejingEvaluation = evaluateKejing($pan, $trace['candidates'], $openIntervalDepths);
    $scopedEvaluation = evaluateScopedCurrentDepth($pan, $trace['candidates'], $currentDepths, $currentMethod);

    $counts['current'][$currentMethod]++;
    $counts['current_depth_same_rank_fudeng'][$currentDepthSameRankFudeng]++;
    $counts['open_interval_depth_same_rank_fudeng'][$openIntervalDepthSameRankFudeng]++;
    $counts['kejIng_open_interval'][$kejIngOpenInterval]++;

    foreach ([
        'current_depth_same_rank_fudeng' => $currentDepthSameRankFudeng,
        'open_interval_depth_same_rank_fudeng' => $openIntervalDepthSameRankFudeng,
        'kejIng_open_interval' => $kejIngOpenInterval,
    ] as $model => $candidateMethod) {
        if ($candidateMethod === $currentMethod) {
            continue;
        }

        $changes[$model][] = [
            'case_id' => $caseId,
            'input' => $case['input'],
            'before' => $currentMethod,
            'after' => $candidateMethod,
            'current_depths' => $currentDepths,
            'open_interval_depths' => $openIntervalDepths,
            'lower_grounds' => array_column($trace['candidates'], 'lower_ground'),
            'uppers' => array_column($trace['candidates'], 'upper'),
            'plate_patterns' => $pan['calculationTrace']['plate_patterns'] ?? [],
        ];
    }

    foreach ([
        'scoped_current_depth_fudeng' => $scopedEvaluation,
        'dinge_open_interval' => $dingeEvaluation,
        'kejing_open_interval' => $kejingEvaluation,
    ] as $model => $evaluation) {
        $candidatePan = applyEvaluation($pan, $evaluation);
        $candidateRecord = PanCreationData::fromCalculatedPan($candidatePan, $case['input']);
        $candidateNormalized = PanRegression::normalize($candidateRecord);
        $differences = PanRegression::fieldDifferences($case['expected'], $candidateNormalized);

        $transition = $currentMethod.' -> '.$evaluation['method'];
        $baselineImpact[$model]['method_transitions'][$transition] = ($baselineImpact[$model]['method_transitions'][$transition] ?? 0) + 1;

        if ($differences === []) {
            continue;
        }

        $baselineImpact[$model]['changed_case_count']++;

        foreach (array_keys($differences) as $field) {
            $baselineImpact[$model]['changed_field_counts'][$field] = ($baselineImpact[$model]['changed_field_counts'][$field] ?? 0) + 1;
        }

        $baselineImpact[$model]['changed_cases'][] = [
            'case_id' => $caseId,
            'input' => $case['input'],
            'method_transition' => $transition,
            'differences' => $differences,
        ];
    }
}

foreach ($baselineImpact as $model => &$impact) {
    ksort($impact['changed_field_counts']);
    ksort($impact['method_transitions']);

    if ($summaryOnly && $model !== 'scoped_current_depth_fudeng') {
        unset($impact['changed_cases']);
    }
}
unset($impact);

$summary = [
    'counts' => $counts,
    'changed_case_counts' => array_map('count', $changes),
    'baseline_impact' => $baselineImpact,
];

if (! $summaryOnly) {
    $summary['changed_cases'] = $changes;
}

echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL;

/**
 * @param  list<array<string, mixed>>  $candidates
 * @param  list<int>  $depths
 */
function classify(array $candidates, array $depths): string
{
    $maximum = max($depths);
    $deepestIndexes = [];

    foreach ($depths as $index => $depth) {
        if ($depth === $maximum) {
            $deepestIndexes[] = $index;
        }
    }

    if (count($deepestIndexes) === 1) {
        return 'shehai';
    }

    $ranks = [];

    foreach ($deepestIndexes as $index) {
        $ranks[$index] = mengZhongJiRank($candidates[$index]['lower_ground']);
    }

    $bestRank = min($ranks);
    $bestIndexes = array_keys(array_filter(
        $ranks,
        static fn (int $rank): bool => $rank === $bestRank,
    ));

    if (count($bestIndexes) > 1) {
        return 'shehai_zhuixia';
    }

    return $bestRank === 0 ? 'shehai_jianji' : 'shehai_chawei';
}

/**
 * 最小假设：保持现有涉害算法不变；仅当现有深度最高者复等，且同属孟、仲或季时，
 * 才按缀瑕的阳日取日干上神、阴日取辰上神。其余结果原样保留。
 *
 * @param  array<string, mixed>  $pan
 * @param  list<array<string, mixed>>  $candidates
 * @param  list<int>  $depths
 * @return array{method: string, initial: int}
 */
function evaluateScopedCurrentDepth(array $pan, array $candidates, array $depths, string $currentMethod): array
{
    $deepestIndexes = deepestIndexes($depths);

    if (count($deepestIndexes) < 2) {
        return ['method' => $currentMethod, 'initial' => $pan['sanchuan0']];
    }

    $ranks = array_unique(array_map(
        static fn (int $index): int => mengZhongJiRank($candidates[$index]['lower_ground']),
        $deepestIndexes,
    ));

    if (count($ranks) !== 1) {
        return ['method' => $currentMethod, 'initial' => $pan['sanchuan0']];
    }

    return ['method' => 'shehai_zhuixia', 'initial' => fudengInitial($pan)];
}

/**
 * 《课经》正文假设：涉害数复相等直接为缀瑕；不等时再看发用所临孟仲季。
 *
 * @param  list<array<string, mixed>>  $candidates
 * @param  list<int>  $depths
 */
function classifyKejIng(array $candidates, array $depths): string
{
    $maximum = max($depths);
    $deepestIndexes = [];

    foreach ($depths as $index => $depth) {
        if ($depth === $maximum) {
            $deepestIndexes[] = $index;
        }
    }

    if (count($deepestIndexes) > 1) {
        return 'shehai_zhuixia';
    }

    $selectedIndex = $deepestIndexes[0];
    $selectedRank = mengZhongJiRank($candidates[$selectedIndex]['lower_ground']);

    if ($selectedRank === 0) {
        return 'shehai_jianji';
    }

    $hasMengCandidate = array_any(
        $candidates,
        static fn (array $candidate): bool => mengZhongJiRank($candidate['lower_ground']) === 0,
    );

    return $hasMengCandidate ? 'shehai' : 'shehai_chawei';
}

/** @return array{changed_case_count: int, changed_field_counts: array<string, int>, method_transitions: array<string, int>, changed_cases: list<array<string, mixed>>} */
function newImpactSummary(): array
{
    return [
        'changed_case_count' => 0,
        'changed_field_counts' => [],
        'method_transitions' => [],
        'changed_cases' => [],
    ];
}

/**
 * @param  array<string, mixed>  $pan
 * @param  list<array<string, mixed>>  $candidates
 * @param  list<int>  $depths
 * @return array{method: string, initial: int}
 */
function evaluateDinge(array $pan, array $candidates, array $depths): array
{
    $deepestIndexes = deepestIndexes($depths);

    if (count($deepestIndexes) === 1) {
        return ['method' => 'shehai', 'initial' => $candidates[$deepestIndexes[0]]['upper']];
    }

    $ranks = [];

    foreach ($deepestIndexes as $index) {
        $ranks[$index] = mengZhongJiRank($candidates[$index]['lower_ground']);
    }

    $bestRank = min($ranks);
    $bestIndexes = array_keys(array_filter(
        $ranks,
        static fn (int $rank): bool => $rank === $bestRank,
    ));

    if (count($bestIndexes) > 1) {
        return ['method' => 'shehai_zhuixia', 'initial' => fudengInitial($pan)];
    }

    return [
        'method' => $bestRank === 0 ? 'shehai_jianji' : 'shehai_chawei',
        'initial' => $candidates[$bestIndexes[0]]['upper'],
    ];
}

/**
 * @param  array<string, mixed>  $pan
 * @param  list<array<string, mixed>>  $candidates
 * @param  list<int>  $depths
 * @return array{method: string, initial: int}
 */
function evaluateKejing(array $pan, array $candidates, array $depths): array
{
    $deepestIndexes = deepestIndexes($depths);

    if (count($deepestIndexes) > 1) {
        return ['method' => 'shehai_zhuixia', 'initial' => fudengInitial($pan)];
    }

    $selectedIndex = $deepestIndexes[0];
    $selectedRank = mengZhongJiRank($candidates[$selectedIndex]['lower_ground']);

    if ($selectedRank === 0) {
        $method = 'shehai_jianji';
    } else {
        $hasMengCandidate = array_any(
            $candidates,
            static fn (array $candidate): bool => mengZhongJiRank($candidate['lower_ground']) === 0,
        );
        $method = $hasMengCandidate ? 'shehai' : 'shehai_chawei';
    }

    return ['method' => $method, 'initial' => $candidates[$selectedIndex]['upper']];
}

/** @param list<int> $depths */
function deepestIndexes(array $depths): array
{
    $maximum = max($depths);

    return array_keys(array_filter(
        $depths,
        static fn (int $depth): bool => $depth === $maximum,
    ));
}

/** @param array<string, mixed> $pan */
function fudengInitial(array $pan): int
{
    return PanCalculator::$yinyangTian[$pan['rigan']] === 1
        ? $pan['sike'][1]
        : $pan['sike'][5];
}

/**
 * @param  array<string, mixed>  $pan
 * @param  array{method: string, initial: int}  $evaluation
 * @return array<string, mixed>
 */
function applyEvaluation(array $pan, array $evaluation): array
{
    $pan['sanchuan0'] = $evaluation['initial'];
    $isFanyin = in_array('fanyin', $pan['calculationTrace']['plate_patterns'] ?? [], true);

    if ($isFanyin) {
        $pan['sanchuan1'] = PanCalculator::$chong[$pan['sanchuan0']];
        $pan['sanchuan2'] = PanCalculator::$chong[$pan['sanchuan1']];
    } else {
        $pan['sanchuan1'] = $pan['tianpan'][$pan['sanchuan0']];
        $pan['sanchuan2'] = $pan['tianpan'][$pan['sanchuan1']];
        $pan['jiuzongmen'] = [
            'shehai' => 5,
            'shehai_jianji' => 6,
            'shehai_chawei' => 7,
            'shehai_zhuixia' => 8,
        ][$evaluation['method']];
    }

    $dayIndex = array_search([$pan['rigan'], $pan['rizhi']], PanCalculator::$jiazi2Ganzhi, true);
    $xunIndex = intdiv($dayIndex, 10);
    $xunFirstZhi = [0, 10, 8, 6, 4, 2];

    foreach (range(0, 2) as $index) {
        $transmission = $pan["sanchuan{$index}"];
        $pan["liuqin{$index}"] = PanCalculator::getShengke(
            PanCalculator::$wuxingDi[$transmission],
            PanCalculator::$wuxingTian[$pan['rigan']],
        )[0];
        $pan["xundun{$index}"] = ($transmission - $xunFirstZhi[$xunIndex] + 12) % 12;
        $tianpanIndex = array_search($transmission, $pan['tianpan'], true);
        $pan["sanchuan{$index}tianjiang"] = $pan['tianjiang'][$tianpanIndex];
    }

    return $pan;
}

function mengZhongJiRank(int $ground): int
{
    if (in_array($ground, [2, 5, 8, 11], true)) {
        return 0;
    }

    if (in_array($ground, [0, 3, 6, 9], true)) {
        return 1;
    }

    return 2;
}

/** @param array<string, mixed> $candidate */
function openIntervalDepth(array $candidate, string $relation): int
{
    $depth = 0;
    $ground = $candidate['lower_ground'];
    $upper = $candidate['upper'];

    for ($position = ($ground + 1) % 12; $position !== $upper; $position = ($position + 1) % 12) {
        if (isHarm($relation, PanCalculator::$wuxingDi[$position], PanCalculator::$wuxingDi[$upper])) {
            $depth++;
        }

        foreach (array_keys(PanCalculator::$jigong, $position, true) as $stem) {
            if (isHarm($relation, PanCalculator::$wuxingTian[$stem], PanCalculator::$wuxingDi[$upper])) {
                $depth++;
            }
        }
    }

    return $depth;
}

function isHarm(string $relation, int $sourceElement, int $upperElement): bool
{
    if ($relation === '下贼上') {
        return PanCalculator::getShengke($sourceElement, $upperElement)[0] === 1;
    }

    return PanCalculator::getShengke($upperElement, $sourceElement)[0] === 1;
}
