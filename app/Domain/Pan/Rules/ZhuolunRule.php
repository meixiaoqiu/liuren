<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：按《六壬大全》判断卯加庚（申）、辛（酉）发用所成的斫轮课。 */
final class ZhuolunRule implements PanRule
{
    protected const RULE_CODE = 'lesson.zhuolun';

    protected const NAME = '斫轮课';

    protected const GROUP = '六十四课';

    protected const DESCRIPTION = '初传卯（车轮）加临地盘申（庚）或酉（辛，刀斧）发用；木就金斫，革故鼎新。';

    protected const GUA = '颐';

    protected const GUA_SYMBOL = '䷚';

    protected const XIANG = '木欲成器，须假金斫，孕病凶险，财喜欢跃，禄位加增，官职超擢，戌印常绶，遇之犹乐。';

    /** @var list<string> 尚未覆盖的原文判断项 */
    private const UNCOVERED = [
        '天乙、青龙、太常、太阴、六合吉将入传（“主官爵践公卿之位”，未实现）',
        '驿马德合吉神入传（未实现）',
        '戌为印、太常为绶入传（未实现）',
        '壬癸日见水神为舟楫、初末有马引从为轩车（未实现）',
        '木休囚乘白虎为棺椁（未实现）',
        '春季甲乙日寅卯时伤斧、秋季庚辛日申酉时伤轮（未实现）',
        '辛卯日干上卯财就人（未实现）',
        '木日艰难、火日灾疾、金日获福、水日心不定、土日流转（五行日干，未实现）',
    ];

    /** @var array<int, int> 五行墓库：木墓未、火土墓戌、金墓丑、水墓辰 */
    private const ELEMENT_GRAVE_BRANCH = [
        0 => 7,
        1 => 10,
        2 => 10,
        3 => 1,
        4 => 4,
    ];

    /** @var list<string> */
    private const BRANCH_NAMES = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $initial = $facts->get('sanchuan0');
        $tianpan = $facts->get('tianpan');

        // 初传卯（3），且天盘卯加临地盘申（8，庚）或酉（9，辛）。
        if ($initial !== 3 || ! is_array($tianpan)) {
            return null;
        }

        $sitsOverGeng = ($tianpan[8] ?? null) === 3;
        $sitsOverXin = ($tianpan[9] ?? null) === 3;

        if (! $sitsOverGeng && ! $sitsOverXin) {
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
                'foundations' => [
                    [
                        'title' => '卯加庚辛',
                        'detail' => '天盘卯（车轮）加临地盘'.($sitsOverGeng ? '申（庚）' : '酉（辛）').'，木就金斫。',
                    ],
                    [
                        'title' => '卯为用',
                        'detail' => '初传太冲卯发用。',
                    ],
                ],
                'judgments' => $this->judgments($facts),
                'uncovered' => self::UNCOVERED,
            ],
        );
    }

    /** @return list<array{code: string, effect: string, label: string, evidence: string}> */
    private function judgments(PanFacts $facts): array
    {
        $judgments = [];
        $transmissions = [
            $facts->get('sanchuan0'),
            $facts->get('sanchuan1'),
            $facts->get('sanchuan2'),
        ];

        // 值空亡为朽木难雕：初传卯落旬空。
        $xundun0 = $facts->get('xundun0');

        if ($xundun0 === 10 || $xundun0 === 11) {
            $judgments[] = [
                'code' => 'rotten_wood',
                'effect' => 'reduce',
                'label' => '朽木难雕',
                'evidence' => '初传卯落旬空，原文主朽木难雕、须另改业。',
            ];
        }

        // 传见本日墓神为旧轮再斫：三传见日干墓支。
        $dayStem = $facts->get('rigan');

        if (is_int($dayStem)) {
            $element = $facts->stemElement($dayStem);
            $grave = is_int($element) ? (self::ELEMENT_GRAVE_BRANCH[$element] ?? null) : null;

            if ($grave !== null && in_array($grave, $transmissions, true)) {
                $judgments[] = [
                    'code' => 'old_wheel_rehewn',
                    'effect' => 'reduce',
                    'label' => '旧轮再斫',
                    'evidence' => '三传见日墓'.self::BRANCH_NAMES[$grave].'，原文主退官失职、再谋复兴。',
                ];
            }
        }

        return $judgments;
    }
}
