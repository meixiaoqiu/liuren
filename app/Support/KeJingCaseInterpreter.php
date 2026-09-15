<?php

namespace App\Support;

use App\Data\PanResult;
use App\Domain\Pan\FateCalculator;
use App\Domain\Pan\Rules\PanRuleEngine;
use App\Services\PanCalculator;
use Carbon\CarbonImmutable;

/**
 * 文件作用：用 KeJingCatalog 的真实 executable 课例经过正式 PanCalculator + RuleEngine 生成课经详情展示数据。
 *
 * 详情页因此不另抄一套现代描述、象曰或命中证据；这些内容与排盘“解盘信息”共用 RuleMatch 数据。
 * 多个 executable 课例的 judgments 会合并，用于详情页尽可能完整地列出当前已实现的增益、减损、例外与修证。
 */
final readonly class KeJingCaseInterpreter
{
    public function __construct(
        private PanCalculator $calculator,
        private PanRuleEngine $ruleEngine,
        private FateCalculator $fateCalculator,
    ) {}

    /** @return array<string, mixed> */
    public function build(array $lesson): array
    {
        $evaluated = [];

        foreach ($lesson['cases'] ?? [] as $case) {
            if (($case['status'] ?? 'executable') !== 'executable') {
                continue;
            }

            $evaluation = $this->evaluateCase($case, (string) $lesson['code']);
            if ($evaluation !== null) {
                $evaluated[] = $evaluation;
            }
        }

        $canonical = $evaluated[0] ?? null;
        $interpretation = $canonical['interpretation'] ?? [
            'code' => $lesson['code'],
            'name' => $lesson['name'],
            'group' => '六十四课',
            'description' => $lesson['summary'],
            'marker' => '经',
            'gua' => $lesson['gua'],
            'guaSymbol' => $lesson['guaSymbol'],
            'xiang' => null,
            'evidence' => [],
            'coverageAreas' => [],
        ];

        return [
            'interpretation' => $interpretation,
            'pan' => $canonical['pan'] ?? null,
            'canonicalCase' => $canonical['case'] ?? null,
            'xundunLabels' => $canonical['xundunLabels'] ?? [],
            'judgments' => $this->collectJudgments($evaluated),
            'uncovered' => $this->collectUncovered($evaluated),
        ];
    }

    /** @return array<string, mixed>|null */
    private function evaluateCase(array $case, string $lessonCode): ?array
    {
        $datetime = CarbonImmutable::createFromFormat('Y-m-d\\TH:i', $case['datetime'], 'Asia/Shanghai');
        $birth = CarbonImmutable::createFromFormat('Y-m-d\\TH:i', $case['birth'], 'Asia/Shanghai');

        $calculated = $this->calculator->calculate($datetime->format('Y-m-d H:i:s'));
        $birthPan = $this->calculator->calculate($birth->format('Y-m-d H:i:s'));
        $fate = $this->fateCalculator->calculate(
            $birthPan->get('nian_index'),
            $calculated->get('nian_index'),
            $case['gender'],
        );

        $people = [[
            'role' => 'querent',
            'birth_datetime' => $case['birth'],
            'gender' => $case['gender'],
            'nianming' => $fate['nianming'],
            'xingnian' => $fate['xingnian'],
            'xingnian_gan' => $fate['xingnian_gan'],
        ]];

        foreach ($case['people'] ?? [] as $person) {
            $personBirth = CarbonImmutable::createFromFormat('Y-m-d\\TH:i', $person['birth_datetime'], 'Asia/Shanghai');
            $personBirthPan = $this->calculator->calculate($personBirth->format('Y-m-d H:i:s'));
            $personFate = $this->fateCalculator->calculate(
                $personBirthPan->get('nian_index'),
                $calculated->get('nian_index'),
                $person['gender'],
            );
            $people[] = [
                'role' => $person['role'],
                'birth_datetime' => $person['birth_datetime'],
                'gender' => $person['gender'],
                'nianming' => $personFate['nianming'],
                'xingnian' => $personFate['xingnian'],
                'xingnian_gan' => $personFate['xingnian_gan'],
            ];
        }

        $result = new PanResult([
            ...$calculated->toArray(),
            ...$fate,
            'context' => ['people' => $people],
        ]);

        $match = collect($this->ruleEngine->evaluate($result))->first(
            static fn ($candidate): bool => $candidate->code === $lessonCode,
        );

        if ($match === null) {
            return null;
        }

        $pan = $result->toArray();

        return [
            'case' => $case,
            'interpretation' => $match->toArray(),
            'pan' => $pan,
            'xundunLabels' => $this->xundunLabels($pan),
        ];
    }

    /** @param list<array<string, mixed>> $evaluated @return list<array<string, mixed>> */
    private function collectJudgments(array $evaluated): array
    {
        $judgments = [];

        foreach ($evaluated as $evaluation) {
            foreach ($evaluation['interpretation']['evidence']['judgments'] ?? [] as $judgment) {
                $key = (string) ($judgment['code'] ?? (($judgment['effect'] ?? 'neutral').'|'.($judgment['label'] ?? '')));
                if ($key === '|') {
                    continue;
                }

                if (! isset($judgments[$key])) {
                    $judgments[$key] = [
                        ...$judgment,
                        'examples' => [],
                    ];
                }

                $label = (string) ($evaluation['case']['label'] ?? '目录课例');
                if (! in_array($label, $judgments[$key]['examples'], true)) {
                    $judgments[$key]['examples'][] = $label;
                }
            }
        }

        return array_values($judgments);
    }

    /** @param list<array<string, mixed>> $evaluated @return list<string> */
    private function collectUncovered(array $evaluated): array
    {
        $items = [];

        foreach ($evaluated as $evaluation) {
            foreach ($evaluation['interpretation']['evidence']['uncovered'] ?? [] as $item) {
                if (is_string($item) && $item !== '') {
                    $items[$item] = true;
                }
            }
        }

        return array_keys($items);
    }

    /** @return array<int, string> */
    private function xundunLabels(array $pan): array
    {
        $dayIndex = array_search([$pan['rigan'], $pan['rizhi']], PanCalculator::$jiazi2Ganzhi, true);
        if (! is_int($dayIndex)) {
            return [];
        }

        $xunFirstZhi = [0, 10, 8, 6, 4, 2][intdiv($dayIndex, 10)];

        return array_map(
            static fn (int $branch): string => PanCalculator::$tiangan[($branch - $xunFirstZhi + 12) % 12],
            array_keys(PanCalculator::$dizhi),
        );
    }
}
