<?php

namespace App\Domain\Pan\Rules;

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use LogicException;

/** 文件作用：逐一执行注册规则、收集全部命中结果，并报告尚未覆盖的取传阶段与因上下文缺失而未评估的规则。 */
final readonly class PanRuleEngine
{
    public function __construct(private RuleRegistry $registry = new RuleRegistry) {}

    /** @return list<RuleMatch> */
    public function evaluate(PanResult $pan): array
    {
        $facts = PanFacts::from($pan);
        $matches = [];
        $registeredCodes = [];

        foreach ($this->registry->rules() as $rule) {
            if (isset($registeredCodes[$rule->code()])) {
                throw new LogicException('Duplicate pan rule code: '.$rule->code());
            }

            $registeredCodes[$rule->code()] = true;

            if ($this->missingContext($rule, $facts) !== []) {
                continue;
            }

            $match = $rule->match($facts);

            if ($match !== null) {
                $matches[] = $match;
            }
        }

        return $matches;
    }

    /**
     * 因缺少必要上下文而未执行的规则（区别于“信息完整但条件不成立”）。
     *
     * @return list<array{code: string, name: string, notice: string}>
     */
    public function notEvaluated(PanResult $pan): array
    {
        $facts = PanFacts::from($pan);
        $notEvaluated = [];

        foreach ($this->registry->rules() as $rule) {
            if ($rule instanceof ContextAwareRule && $this->missingContext($rule, $facts) !== []) {
                $notEvaluated[] = [
                    'code' => $rule->code(),
                    ...$rule->notEvaluatedInfo(),
                ];
            }
        }

        return $notEvaluated;
    }

    /** @return list<string> */
    private function missingContext(PanRule $rule, PanFacts $facts): array
    {
        if (! $rule instanceof ContextAwareRule) {
            return [];
        }

        $missing = [];

        foreach ($rule->requiredContext() as $path) {
            $parts = explode('.', $path, 3);

            if ($parts[0] !== 'people') {
                continue;
            }

            $person = $facts->personByRole($parts[1] ?? '');
            if ($person === null) {
                $missing[] = $path;

                continue;
            }

            // 三段式路径：people.<role>.<field>，要求字段存在且非 null
            if (isset($parts[2])) {
                $alternativeFields = explode('|', $parts[2]);
                $hasAnyField = array_any(
                    $alternativeFields,
                    fn (string $field): bool => array_key_exists($field, $person) && $person[$field] !== null,
                );

                if (! $hasAnyField) {
                    $missing[] = $path;
                }
            }
        }

        return $missing;
    }

    /** @return list<string> */
    public function coverageNotices(PanResult $pan): array
    {
        $labels = [
            'initial_transmission' => '初传取法',
            'middle_transmission' => '中传取法',
            'final_transmission' => '末传取法',
        ];

        $coveredAreas = [];

        foreach ($this->evaluate($pan) as $match) {
            foreach ($match->coverageAreas as $area) {
                $coveredAreas[$area] = true;
            }
        }

        $notices = [];

        foreach ($labels as $area => $label) {
            if (! isset($coveredAreas[$area])) {
                $notices[] = $label.'规则尚未覆盖。';
            }
        }

        return $notices;
    }
}
