<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/** 文件作用：按“斗系日本 AND（初传囚 OR 死 OR 日墓）”冻结口径判断天狱课。 */
final class TianyuRule implements PanRule
{
    /** @var array<int, int> 六壬五行长生位：阴阳干同取，不用阴干逆行十二长生。 */
    public const DAY_ORIGIN = [11, 11, 2, 2, 8, 8, 5, 5, 8, 8];

    /** @var array<int, int> 五行墓：木未、火戌、土辰、金丑、水辰。 */
    public const DAY_GRAVE = [7, 7, 10, 10, 4, 4, 1, 1, 4, 4];

    /** @var list<string> */
    public const BRANCH_NAMES = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];

    /** @var list<string> */
    private const UNCOVERED = [
        '俯仰丘仇、正墓加同类暂作课义增强，未加入基础成立条件。',
        '刑、杀、灾、劫、魄化、绞斩与“真天狱”等凶应修证或变格尚未实现。',
        '青龙救解、贵人临辰戌、日辰行年得子孙生气，以及德、合、解神、吉将与“天狱清平”等救解尚未实现。',
    ];

    public function code(): string
    {
        return 'lesson.tianyu';
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $dayStem = $facts->get('rigan');
        $initial = $facts->get('sanchuan0');
        $tianpan = $facts->get('tianpan');

        if (! is_int($dayStem) || ! is_int($initial) || ! is_array($tianpan)) {
            return null;
        }

        $origin = self::DAY_ORIGIN[$dayStem] ?? null;
        $grave = self::DAY_GRAVE[$dayStem] ?? null;
        if ($origin === null || $grave === null || ! isset($tianpan[$origin]) || ! is_int($tianpan[$origin])) {
            return null;
        }

        $originUpper = $tianpan[$origin];
        $douXiRiBen = $originUpper === 4;
        $initialState = $facts->branchSeasonalState($initial);
        $routes = array_values(array_filter([
            $initialState === '囚' ? 'seasonal_qiu' : null,
            $initialState === '死' ? 'seasonal_si' : null,
            $initial === $grave ? 'day_grave' : null,
        ]));

        if (! $douXiRiBen || $routes === []) {
            return null;
        }

        $stemName = PanCalculator::$tiangan[$dayStem] ?? '?';
        $originName = self::BRANCH_NAMES[$origin];
        $originUpperName = self::BRANCH_NAMES[$originUpper] ?? '?';
        $initialName = self::BRANCH_NAMES[$initial] ?? '?';
        $graveName = self::BRANCH_NAMES[$grave];
        $routeLabels = array_map(static fn (string $route): string => match ($route) {
            'seasonal_qiu' => '囚气发用',
            'seasonal_si' => '死气发用',
            'day_grave' => '墓神发用',
        }, $routes);

        return new RuleMatch(
            code: $this->code(),
            name: '天狱课',
            group: '六十四课',
            description: '初传为时令囚、死或日墓，且天罡辰临日干长生位。',
            gua: '噬嗑',
            guaSymbol: '䷔',
            xiang: '日用迍邅，刑狱之愆。犯法难解，染病未痊。出行凶也，谋事徒然。兵家大忌，出军不旋。',
            evidence: [
                'day_stem' => $dayStem,
                'day_origin' => $origin,
                'day_origin_upper' => $originUpper,
                'dou_xi_ri_ben' => $douXiRiBen,
                'initial' => $initial,
                'initial_seasonal_state' => $initialState,
                'day_grave' => $grave,
                'matched_initial_routes' => $routes,
                'foundations' => [
                    ['title' => '斗系日本', 'detail' => "{$stemName}日长生在{$originName}，地盘{$originName}上见{$originUpperName}；天罡辰临日本成立。"],
                    ['title' => '囚死墓神发用', 'detail' => "初传{$initialName}，当前时令为{$initialState}；{$stemName}日日墓为{$graveName}。本盘命中".implode('、', $routeLabels).'。'],
                ],
                'judgments' => [],
                'uncovered' => self::UNCOVERED,
            ],
        );
    }
}
