<?php

namespace App\Support;

use App\Data\PanResult;
use App\Domain\Pan\FateCalculator;
use App\Domain\Pan\Rules\PanRule;
use App\Domain\Pan\Rules\PanRuleEngine;
use App\Domain\Pan\Rules\RuleRegistry;
use App\Services\PanCalculator;
use Carbon\CarbonImmutable;

/**
 * 文件作用：用 KeJingCatalog 的真实 executable 课例经过正式 PanCalculator + RuleEngine 生成课经详情展示数据。
 *
 * 详情页不另抄一套现代描述、象曰或命中证据；这些内容与排盘“解盘信息”共用 RuleMatch 数据。
 * 详情页的完整静态课义（成课条件、增益减损例外条件）来自 PanRule::definition() 的结构化输出，
 * 不再通过“多个 executable 课例的 judgments 并集”反推完整定义。
 */
final readonly class KeJingCaseInterpreter
{
    public function __construct(
        private PanCalculator $calculator,
        private PanRuleEngine $ruleEngine,
        private FateCalculator $fateCalculator,
        private RuleRegistry $registry,
    ) {}

    /** @return array<string, mixed> */
    public function build(array $lesson): array
    {
        $canonical = null;

        foreach ($lesson['cases'] ?? [] as $case) {
            if (($case['status'] ?? 'executable') !== 'executable') {
                continue;
            }

            $canonical = $this->evaluateCase(
                $case,
                (string) $lesson['code'],
                (string) $lesson['name'],
            );
            if ($canonical !== null) {
                break;
            }
        }
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
            'staticDefinition' => $this->resolveStaticDefinition((string) $lesson['code']),
            'pan' => $canonical['pan'] ?? null,
            'canonicalCase' => $canonical['case'] ?? null,
            'xundunLabels' => $canonical['xundunLabels'] ?? [],
            'grids' => $canonical['grids'] ?? [],
            'uncovered' => array_values(array_filter(
                $canonical['interpretation']['evidence']['uncovered'] ?? [],
                static fn ($item): bool => is_string($item) && $item !== '',
            )),
        ];
    }

    /**
     * 通过 RuleRegistry 找到对应 code 的 PanRule 并调用其 definition()。
     * 找不到对应 Rule 时返回空骨架，详情页会显示“尚未结构化录入”提示。
     *
     * @return array{
     *     description: string,
     *     xiang: ?string,
     *     foundations: list<array{code: string, title: string, description: string}>,
     *     judgments: list<array{code: string, effect: string, label: string, description: string}>
     * }
     */
    private function resolveStaticDefinition(string $lessonCode): array
    {
        foreach ($this->registry->rules() as $rule) {
            if ($rule->code() === $lessonCode) {
                return $rule->definition();
            }
        }

        return [
            'description' => '',
            'xiang' => null,
            'foundations' => [],
            'judgments' => [],
        ];
    }

    /** @return array<string, mixed>|null */
    private function evaluateCase(array $case, string $lessonCode, string $lessonName): ?array
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

        $matches = collect($this->ruleEngine->evaluate($result));
        $match = $matches->first(
            static fn ($candidate): bool => $candidate->code === $lessonCode,
        );

        if ($match === null) {
            return null;
        }

        $lessonBaseName = preg_replace('/课$/u', '', $lessonName) ?: $lessonName;
        $gridGroup = $lessonBaseName.'课体';
        $grids = $matches
            ->filter(
                static fn ($candidate): bool => $candidate->marker === '格'
                    && $candidate->group === $gridGroup,
            )
            ->map(static fn ($candidate): array => $candidate->toArray())
            ->values()
            ->all();

        $pan = $result->toArray();

        return [
            'case' => $case,
            'interpretation' => $match->toArray(),
            'grids' => $grids,
            'pan' => $pan,
            'xundunLabels' => $this->xundunLabels($pan),
        ];
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
