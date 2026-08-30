<?php

namespace App\Domain\Pan\Rules;

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use LogicException;

/** 文件作用：逐一执行注册规则、收集全部命中结果，并报告尚未覆盖的取传阶段。 */
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
            $match = $rule->match($facts);

            if ($match !== null) {
                $matches[] = $match;
            }
        }

        return $matches;
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
