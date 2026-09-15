<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/** 文件作用：判断励德课下「日辰阴阳俱在天乙后」的微服格。 */
final class WeifuRule implements PanRule
{
    use LessonDefinitionDefaults;

    public function code(): string
    {
        return 'structure.weifu';
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $noblemanGround = LideSupport::noblemanGround($facts);
        $fourGods = $noblemanGround === null ? null : LideSupport::fourGods($facts);

        if ($fourGods === null || LideSupport::pattern($fourGods) !== 'weifu') {
            return null;
        }

        $groundName = PanCalculator::$dizhi[$noblemanGround] ?? '?';

        return new RuleMatch(
            code: $this->code(),
            name: '微服格',
            group: '励德课体',
            description: '励德课中，日阳、日阴、辰阳、辰阴四神全部乘贵后六将。',
            marker: '格',
            evidence: [
                'nobleman_ground' => $noblemanGround,
                'four_gods' => $fourGods,
                'detail' => "贵人临{$groundName}；".LideSupport::describe($fourGods).'。四神俱在贵后六将，成微服格；古籍主君子迁官、小人退职，事体稍迟。',
            ],
        );
    }
}
