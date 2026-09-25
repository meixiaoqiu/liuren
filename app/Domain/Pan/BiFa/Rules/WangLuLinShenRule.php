<?php

namespace App\Domain\Pan\BiFa\Rules;

use App\Domain\Pan\BiFa\BiFaRule;
use App\Domain\Pan\BiFa\BiFaRuleMatch;
use App\Domain\Pan\Facts\PanFacts;
use App\Support\BiFaCatalog;
use LogicException;

/** 《六壬大全·毕法赋》第七法「旺禄临身徒妄作」。 */
final class WangLuLinShenRule implements BiFaRule
{
    /** 阴干日禄：乙卯、丁午、己午、辛酉、癸子。 */
    private const DAY_LU = [1 => 3, 3 => 6, 5 => 6, 7 => 9, 9 => 0];

    private const GENERAL_BAIHU = 7;

    private const GENERAL_XUANWU = 9;

    public function code(): string
    {
        return 'bifa.07';
    }

    public function law(): array
    {
        $law = BiFaCatalog::findByCode($this->code());
        if ($law === null) {
            throw new LogicException('BiFaCatalog 找不到 '.$this->code().'；注册表与目录脱节。');
        }

        return $law;
    }

    public function definition(): array
    {
        return [
            'description' => '乙、丁、己、辛、癸阴干之日，本干日禄正临日干之上，以守现有之禄为本义；阳干禄临干属于伏吟体系，不纳入本法。',
            'foundations' => [[
                'code' => 'wang_lu_on_stem',
                'title' => '旺禄临身',
                'description' => '乙、丁、己、辛、癸阴干之日，本干日禄正临日干之上。',
            ]],
            'judgments' => [
                ['label' => '宜守旺禄', 'effect' => 'neutral', 'description' => '日禄正临日干，现有根基已有可守之处，宜守成，不宜舍近逐远、另谋妄动。'],
                ['label' => '旺禄旬空', 'effect' => 'resolve', 'description' => '日禄虽临干而落旬空，已有之禄不足恃，不再单以守禄论，应转察三传。'],
                ['label' => '闭口禄', 'effect' => 'resolve', 'description' => '日禄本身为本旬旬尾，虽禄临干亦不可安守。'],
                ['label' => '禄被玄武夺', 'effect' => 'resolve', 'description' => '旺禄乘玄武，古籍称禄被元夺，已有之禄不可安守。'],
                ['label' => '旺禄乘白虎', 'effect' => 'reduce', 'description' => '旺禄乘白虎，守禄之象受损，仍须结合课传制化判断，不可仅凭白虎一项即断禄不可守。'],
            ],
            'sections' => [
                ['title' => '为什么只取阴干', 'content' => '本法专取乙、丁、己、辛、癸五个阴干。甲、丙、戊、庚、壬五个阳干即使日禄临干，也天然属于伏吟结构，古籍明确称其不在本例。'],
                ['title' => '旺禄不取月令旺相', 'content' => '这里的旺禄专指本干日禄临于干上，不另查月令，也不以旺、相、休、囚、死或三传发用增减成立条件。'],
                ['title' => '特殊判断不取消成立', 'content' => '旬空、闭口禄、玄武夺禄可解除普通“宜守旺禄”的判断；白虎乘禄只作为减损条件，本身不足以一律推翻守禄结论，还须结合课传制化判断。多种特殊判断可以同时保留，均不取消旺禄临身本身。'],
                ['title' => '闭口禄与闭口课不同', 'content' => '闭口禄只看日禄是否等于本旬旬尾，不复用课经闭口课的判定，也不要求旬尾加旬首、发用或乘玄武。'],
            ],
        ];
    }

    public function match(PanFacts $facts): ?BiFaRuleMatch
    {
        $rigan = $facts->get('rigan');
        $sike = $facts->get('sike');
        if (! is_int($rigan) || ! array_key_exists($rigan, self::DAY_LU)
            || ! is_array($sike) || ! array_key_exists(1, $sike)
            || ! is_int($sike[1]) || $sike[1] < 0 || $sike[1] > 11) {
            return null;
        }

        $dayLu = self::DAY_LU[$rigan];
        if ($sike[1] !== $dayLu) {
            return null;
        }

        $luVoid = $facts->isBranchXunVoid($dayLu);
        $xunHead = $facts->dayXunHeadBranch();
        $xunTail = $xunHead === null ? null : ($xunHead + 9) % 12;
        $closedMouthLu = $xunTail === null ? null : $dayLu === $xunTail;
        $luGeneral = $facts->generalRidingBranch($dayLu);
        $xuanwu = $luGeneral === null ? null : $luGeneral === self::GENERAL_XUANWU;
        $baihu = $luGeneral === null ? null : $luGeneral === self::GENERAL_BAIHU;

        $judgments = [];
        if ($luVoid) {
            $judgments[] = ['label' => '旺禄旬空', 'effect' => 'resolve', 'description' => '日禄虽临干而落旬空，已有之禄不足恃，不再单以守禄论，应转察三传。'];
        }
        if ($closedMouthLu) {
            $judgments[] = ['label' => '闭口禄', 'effect' => 'resolve', 'description' => '日禄本身为本旬旬尾，虽禄临干亦不可安守。'];
        }
        if ($xuanwu) {
            $judgments[] = ['label' => '禄被玄武夺', 'effect' => 'resolve', 'description' => '旺禄乘玄武，古籍称禄被元夺，已有之禄不可安守。'];
        }
        if ($baihu === true) {
            $judgments[] = ['label' => '旺禄乘白虎', 'effect' => 'reduce', 'description' => '旺禄乘白虎，守禄之象受损，仍须结合课传制化判断，不可仅凭白虎一项即断禄不可守。'];
        }

        $factsComplete = $luVoid !== null && $closedMouthLu !== null && $xuanwu !== null;
        $hardResolvers = $luVoid === true || $closedMouthLu === true || $xuanwu === true;
        if ($factsComplete && ! $hardResolvers) {
            $judgments[] = ['label' => '宜守旺禄', 'effect' => 'neutral', 'description' => '日禄正临日干，现有根基已有可守之处，宜守成，不宜舍近逐远、另谋妄动。'];
        }

        $law = $this->law();

        return new BiFaRuleMatch(
            code: $this->code(), number: $law['number'], name: $law['name'], summary: $law['summary'],
            subMatches: [[
                'code' => 'wang_lu_on_stem', 'title' => '旺禄临身',
                'description' => '阴干日禄正临日干之上。', 'matched' => true,
                'detail' => '阴干日禄正临日干之上。', 'requires_people' => false, 'people_missing' => false,
            ]],
            matchedRoutes: ['wang_lu_on_stem'],
            evidence: [
                'day_lu' => $dayLu, 'xun_head' => $xunHead, 'xun_tail' => $xunTail,
                'lu_void' => $luVoid, 'closed_mouth_lu' => $closedMouthLu, 'lu_general' => $luGeneral,
            ],
            matchedJudgments: $judgments,
        );
    }
}
