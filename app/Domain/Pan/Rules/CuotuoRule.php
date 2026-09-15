<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/** 文件作用：判断励德课下「日辰阴阳俱在天乙前」的蹉跎格。 */
final class CuotuoRule implements PanRule
{
    use LessonDefinitionDefaults;

    public function code(): string
    {
        return 'structure.cuotuo';
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $noblemanGround = LideSupport::noblemanGround($facts);
        $fourGods = $noblemanGround === null ? null : LideSupport::fourGods($facts);

        if ($fourGods === null || LideSupport::pattern($fourGods) !== 'cuotuo') {
            return null;
        }

        $groundName = PanCalculator::$dizhi[$noblemanGround] ?? '?';

        return new RuleMatch(
            code: $this->code(),
            name: '蹉跎格',
            group: '励德课体',
            description: '励德课中，日阳、日阴、辰阳、辰阴四神全部乘贵前五将。',
            marker: '格',
            evidence: [
                'nobleman_ground' => $noblemanGround,
                'four_gods' => $fourGods,
                'detail' => "贵人临{$groundName}；".LideSupport::describe($fourGods).'。四神俱在贵前五将，成蹉跎格；古籍主小人进职、君子退位，事体稍迟。',
            ],
        );
    }
}
