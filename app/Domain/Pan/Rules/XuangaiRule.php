<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：按《六壬大全》判断胜光发用、太冲神后入传所成的轩盖课，并罗列已核实的课义条件与客观盘面信息。 */
final class XuangaiRule implements PanRule
{
    protected const RULE_CODE = 'lesson.xuangai';

    protected const NAME = '轩盖课';

    protected const GROUP = '六十四课';

    protected const DESCRIPTION = '胜光午为天马发用，太冲卯为天车居中传，神后子为华盖居末传，三传为午、卯、子。';

    protected const GUA = '升';

    protected const GUA_SYMBOL = '䷭';

    protected const XIANG = '课遇高轩，车马皆全，朱轮稳上，诏用荣宣，求财大获，疾病难延，干贵欢会，行者必旋。';

    /** @var list<string> */
    private const GENERAL_NAMES = ['贵人', '螣蛇', '朱雀', '六合', '勾陈', '青龙', '天空', '白虎', '太常', '玄武', '太阴', '天后'];

    /** @var list<string> */
    private const BRANCH_NAMES = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];

    /** @var list<string> */
    private const STEM_NAMES = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];

    /** @var list<string> */
    private const POSITION_NAMES = ['初传', '中传', '末传'];

    /** @var list<string> 尚未覆盖的原文课义条件 */
    private const UNCOVERED = [
        '德神上乘吉将（原文有"德神"前提，尚未落实）',
        '三传带杀（凶煞未实现）',
        '乘蛇虎死气（"死气"未落实）',
        '克年命日辰（未实现）',
        '卯作丧车（未实现）',
        '车马作财（财自外来，未实现）',
    ];

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $transmissions = [
            $facts->get('sanchuan0'),
            $facts->get('sanchuan1'),
            $facts->get('sanchuan2'),
        ];

        if ($transmissions !== [6, 3, 0]) {
            return null;
        }

        return new RuleMatch(
            code: $this->code(),
            name: self::NAME,
            group: self::GROUP,
            description: self::DESCRIPTION,
            gua: self::GUA,
            guaSymbol: self::GUA_SYMBOL,
            xiang: self::XIANG,
            evidence: [
                'transmissions' => $transmissions,
                'initial_branch' => 6,
                'middle_branch' => 3,
                'final_branch' => 0,
                'conditions' => $this->conditions($facts),
                'observations' => $this->observations($facts),
                'uncovered' => self::UNCOVERED,
            ],
        );
    }

    /** @return list<array{code: string, label: string, evidence: string}> */
    private function conditions(PanFacts $facts): array
    {
        $conditions = [];

        $monthBranch = $facts->get('yuezhi');

        if (is_int($monthBranch) && in_array($monthBranch, [2, 8], true)) {
            $monthLabel = $monthBranch === 2 ? '寅（正月）' : '申（七月）';
            $conditions[] = [
                'code' => 'proper_month',
                'label' => '正七月正格',
                'evidence' => '月建为'.$monthLabel.'，天马落午，正格。',
            ];
        }

        $dayStem = $facts->get('rigan');
        $dayStemWang = is_int($dayStem) && $facts->isStemWangOrXiang($dayStem);
        $initialWang = $facts->isBranchWangOrXiang(6);

        if ($dayStemWang && $initialWang) {
            $season = $facts->seasonalPeriod();
            $seasonName = is_array($season) ? $season['name'] : '当前季节';
            $conditions[] = [
                'code' => 'day_and_use_wang_xiang',
                'label' => '日用旺相',
                'evidence' => '日干'.self::STEM_NAMES[$dayStem].'与发用午均得'.$seasonName.'旺相之气。',
            ];
        }

        $xundun = [
            $facts->get('xundun0'),
            $facts->get('xundun1'),
            $facts->get('xundun2'),
        ];
        $emptyDetails = [];

        foreach ($xundun as $position => $value) {
            if ($value === 10 || $value === 11) {
                $emptyDetails[] = self::POSITION_NAMES[$position].self::BRANCH_NAMES[[6, 3, 0][$position]].'落旬空';
            }
        }

        if ($emptyDetails !== []) {
            $conditions[] = [
                'code' => 'empty_transmission',
                'label' => '三传落空亡',
                'evidence' => implode('，', $emptyDetails).'。',
            ];
        }

        return $conditions;
    }

    /** @return list<array{code: string, label: string, evidence: string}> */
    private function observations(PanFacts $facts): array
    {
        $observations = [];

        $ridingGenerals = $this->transmissionGenerals($facts);
        $generalDetails = [];

        foreach ($ridingGenerals as $position => $general) {
            if (is_int($general)) {
                $generalDetails[] = self::POSITION_NAMES[$position].self::BRANCH_NAMES[[6, 3, 0][$position]].'乘'.self::GENERAL_NAMES[$general];
            }
        }

        if ($generalDetails !== []) {
            $observations[] = [
                'code' => 'transmission_generals',
                'label' => '三传所乘天将',
                'evidence' => implode('，', $generalDetails).'。',
            ];
        }

        $yearBranch = $facts->get('nianzhi');
        $monthGeneral = $facts->get('yuejiang');
        $yearMonthDetails = [];

        if (is_int($yearBranch)) {
            $position = array_search($yearBranch, [6, 3, 0], true);
            $yearMonthDetails[] = '太岁'.self::BRANCH_NAMES[$yearBranch].($position === false ? '不在三传' : '在'.self::POSITION_NAMES[$position]);
        }

        if (is_int($monthGeneral)) {
            $position = array_search($monthGeneral, [6, 3, 0], true);
            $yearMonthDetails[] = '月将'.self::BRANCH_NAMES[$monthGeneral].($position === false ? '不在三传' : '在'.self::POSITION_NAMES[$position]);
        }

        if ($yearMonthDetails !== []) {
            $observations[] = [
                'code' => 'year_month_positions',
                'label' => '太岁与月将位置',
                'evidence' => implode('，', $yearMonthDetails).'。',
            ];
        }

        return $observations;
    }

    /** @return list<?int> */
    private function transmissionGenerals(PanFacts $facts): array
    {
        $generals = [];

        foreach (['sanchuan0tianjiang', 'sanchuan1tianjiang', 'sanchuan2tianjiang'] as $key) {
            $general = $facts->get($key);
            $generals[] = is_int($general) ? $general : null;
        }

        return $generals;
    }
}
