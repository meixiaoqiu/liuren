<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/**
 * 文件作用：判断第56课励德课。
 *
 * 冻结口径：天乙贵人临地盘卯或酉即成课。四课阴阳神在贵前、贵后或居中的关系只用于分型与吉凶，
 * 不得反向收紧成课条件；微服、蹉跎分别由独立格规则判断。
 */
final class LideRule implements PanRule
{
    public const RULE_CODE = 'lesson.lide';

    public const NAME = '励德课';

    public const GROUP = '六十四课';

    public const GUA = '随';

    public const GUA_SYMBOL = '䷐';

    public const DESCRIPTION = '天乙贵人临地盘卯或酉即成励德课；四课阴阳神的贵前贵后关系只用于分型与吉凶。';

    public const XIANG = '阳神前引，阴神后随，君子则吉，小人则危。阴神前立，阳神后居，小人得意，君子失机。';

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $noblemanGround = LideSupport::noblemanGround($facts);

        if ($noblemanGround === null) {
            return null;
        }

        $fourGods = LideSupport::fourGods($facts);
        $pattern = $fourGods === null ? null : LideSupport::pattern($fourGods);
        $judgments = [];

        if ($pattern === 'yang_front_yin_rear') {
            $judgments[] = [
                'code' => 'yang_front_yin_rear',
                'effect' => 'increase',
                'label' => '阳前阴后',
                'evidence' => LideSupport::describe($fourGods).'。两阳神在贵前、两阴神在贵后，按《象曰》为「君子则吉，小人则危」。',
            ];
        }

        if ($pattern === 'yin_front_yang_rear') {
            $judgments[] = [
                'code' => 'yin_front_yang_rear',
                'effect' => 'reduce',
                'label' => '阴前阳后',
                'evidence' => LideSupport::describe($fourGods).'。两阴神在贵前、两阳神在贵后，按《象曰》为「小人得意，君子失机」。',
            ];
        }

        $groundName = PanCalculator::$dizhi[$noblemanGround] ?? '?';

        return new RuleMatch(
            code: self::RULE_CODE,
            name: self::NAME,
            group: self::GROUP,
            description: self::DESCRIPTION,
            gua: self::GUA,
            guaSymbol: self::GUA_SYMBOL,
            xiang: self::XIANG,
            evidence: [
                'nobleman_ground' => $noblemanGround,
                'nobleman_ground_name' => $groundName,
                'four_gods' => $fourGods,
                'pattern' => $pattern,
                'pattern_label' => LideSupport::patternLabel($pattern),
                'judgments' => $judgments,
                'condition_detail' => "天乙贵人临地盘{$groundName}，符合卯、酉之一，励德课成课条件成立。",
            ],
        );
    }
}
