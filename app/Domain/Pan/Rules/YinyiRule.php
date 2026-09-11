<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/** 文件作用：按项目冻结口径判断《六壬大全》淫泆课主体。 */
final class YinyiRule implements PanRule
{
    private const MAO_YOU = [3, 9];

    private const HOU_HE = [3, 11];

    public function code(): string
    {
        return 'lesson.yinyi';
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $initial = $facts->get('sanchuan0');
        if (! is_int($initial)) {
            return null;
        }

        $initialGeneral = $facts->generalRidingBranch($initial);
        if (! is_int($initialGeneral)) {
            return null;
        }

        $initialIsMaoyou = in_array($initial, self::MAO_YOU, true);
        $initialGeneralIsHouhe = in_array($initialGeneral, self::HOU_HE, true);
        if (! ($initialIsMaoyou && $initialGeneralIsHouhe)) {
            return null;
        }

        $branch = PanCalculator::$dizhi[$initial] ?? '?';
        $general = PanCalculator::$tianjiang[$initialGeneral] ?? '?';

        return new RuleMatch(
            code: $this->code(),
            name: '淫泆课',
            group: '六十四课',
            description: '初传卯或酉发用，且该初传乘六合或天后。',
            gua: '既济',
            guaSymbol: '䷾',
            xiang: '男子就室，女子有家。阴私莫禁，淫欲转加。嫁娶不吉，逃亡可嘉。捕捉难获，访人自差。',
            evidence: [
                'initial' => $initial,
                'initial_general' => $initialGeneral,
                'initial_is_maoyou' => $initialIsMaoyou,
                'initial_general_is_houhe' => $initialGeneralIsHouhe,
                'foundations' => [
                    ['title' => '卯酉发用', 'detail' => "初传为{$branch}，属于卯酉。"],
                    ['title' => '将乘后合', 'detail' => "同一初传{$branch}乘{$general}。"],
                ],
                'judgments' => [],
                'uncovered' => [
                    '淫泆与三交并见所称浊滥淫泆，以及天罗地网、天烦、地烦、二烦、九丑尚未程序化',
                    '后合临日辰及男女行年、空亡、日用旺相休囚和其他神将吉凶修证尚未程序化',
                ],
            ],
        );
    }
}
