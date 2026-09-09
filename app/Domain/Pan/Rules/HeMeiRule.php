<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\BranchRelations;
use App\Domain\Pan\Facts\PanFacts;

/**
 * 文件作用：按《六壬大全》正文及《观月经》“四和美”前三式的共同结构判断和美课。
 * 《订讹》的单边六合及“生日作财”等扩大口径只记入课经笔记。
 */
final class HeMeiRule implements PanRule
{
    protected const RULE_CODE = 'lesson.he_mei';

    protected const NAME = '和美课';

    protected const GROUP = '六十四课';

    protected const DESCRIPTION = '日干、日支与彼此上神交互作六合，或分别与各自上神作六合，或干支上神与三传中的一传共同构成完整三合。';

    protected const GUA = '丰';

    protected const GUA_SYMBOL = '䷶';

    protected const XIANG = '三合六合，上下欢悦，交易大通，财利不绝。婚吉事成，病危势拙，干贵相宜，占敌和决。';

    /** @var list<string> */
    private const UNCOVERED = [
        '《观月经》“四和美”第四式“四课上下俱作三合”的精确结构尚待进一步校勘',
        '《订讹》单边六合、三传自成三合后见六合或生日作财等扩大口径已归档，暂不作为正文主体入口',
        '还魂债、合带六害或空亡、蜜中砒等增强与减损结构尚未实现',
        '正文课例“日辰旺气”的课义增强尚未实现',
    ];

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $dayStem = $facts->get('rigan');
        $dayBranch = $facts->get('rizhi');
        $tianpan = $facts->get('tianpan');
        $transmissions = [$facts->get('sanchuan0'), $facts->get('sanchuan1'), $facts->get('sanchuan2')];

        if (! is_int($dayStem) || ! is_int($dayBranch) || ! is_array($tianpan)
            || ! array_reduce($transmissions, fn (bool $valid, mixed $value): bool => $valid && is_int($value), true)) {
            return null;
        }

        $stemLodging = $facts->stemLodgingBranch($dayStem);
        if (! is_int($stemLodging) || ! isset($tianpan[$stemLodging], $tianpan[$dayBranch])
            || ! is_int($tianpan[$stemLodging]) || ! is_int($tianpan[$dayBranch])) {
            return null;
        }

        $dayUpper = $tianpan[$stemLodging];
        $branchUpper = $tianpan[$dayBranch];
        $crossLiuhe = BranchRelations::isLiuhe($stemLodging, $branchUpper)
            && BranchRelations::isLiuhe($dayBranch, $dayUpper);
        $sameLiuhe = BranchRelations::isLiuhe($stemLodging, $dayUpper)
            && BranchRelations::isLiuhe($dayBranch, $branchUpper);

        $sanheTransmission = null;
        foreach ($transmissions as $position => $transmission) {
            if (BranchRelations::isSanhe($dayUpper, $branchUpper, $transmission)) {
                $sanheTransmission = ['position' => $position, 'branch' => $transmission];
                break;
            }
        }
        $upperSanheTransmission = $sanheTransmission !== null;

        if (! ($crossLiuhe || $sameLiuhe || $upperSanheTransmission)) {
            return null;
        }

        $matchedStructures = [];
        if ($crossLiuhe) {
            $matchedStructures[] = 'cross_liuhe';
        }
        if ($sameLiuhe) {
            $matchedStructures[] = 'same_liuhe';
        }
        if ($upperSanheTransmission) {
            $matchedStructures[] = 'upper_sanhe_transmission';
        }

        return new RuleMatch(
            code: $this->code(), name: self::NAME, group: self::GROUP,
            description: self::DESCRIPTION, gua: self::GUA, guaSymbol: self::GUA_SYMBOL, xiang: self::XIANG,
            evidence: [
                'day_stem' => $dayStem, 'day_branch' => $dayBranch,
                'day_stem_lodging_branch' => $stemLodging, 'day_upper' => $dayUpper, 'branch_upper' => $branchUpper,
                'initial' => $transmissions[0], 'middle' => $transmissions[1], 'final' => $transmissions[2],
                'matched_structures' => $matchedStructures,
                'cross_liuhe' => $crossLiuhe, 'same_liuhe' => $sameLiuhe,
                'upper_sanhe_transmission' => $upperSanheTransmission,
                'sanhe_transmission_detail' => $sanheTransmission,
                'uncovered' => self::UNCOVERED,
            ],
        );
    }
}
