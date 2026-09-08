<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/**
 * 文件作用：按《六壬大全》现阶段采用的综合口径判断荣华课。
 *
 * 禄、马、贵三类须齐见于干上、支上、本命上、行年上或三传；三者之一以旺相气发用；
 * 三传中的相关神至少一处乘吉将。本命、行年存在时参与，不存在时不强制。
 *
 * 该公式综合总定义及丙寅、壬申、丙申三例而定；原文断句、“更乘吉将”的约束范围、
 * 丙申古例卯时取昼贵等问题仍有解释边界，详见 docs/课经/25-荣华课.md。
 */
final class RongHuaRule implements PanRule
{
    /** @var array<int, int> 日干禄位 */
    private const DAY_STEM_LU = [2, 3, 5, 6, 5, 6, 8, 9, 11, 0];

    /** @var array<int, int> 日支驿马位 */
    private const TRAVEL_HORSE = [2, 11, 8, 5, 2, 11, 8, 5, 2, 11, 8, 5];

    /** @var list<int> 贵、合、龙、常、阴、后 */
    private const AUSPICIOUS_GENERALS = [0, 3, 5, 8, 10, 11];

    protected const RULE_CODE = 'lesson.rong_hua';

    protected const NAME = '荣华课';

    protected const GROUP = '六十四课';

    protected const DESCRIPTION = '禄马贵人齐见于干支年命或三传，三者之一旺相发用，传中相关神更乘吉将。';

    protected const GUA = '渐';

    protected const GUA_SYMBOL = '䷴';

    protected const XIANG = '干支吉神，入宅俱利。经营俱亨，动止均美。孕育麟儿，婚成连理。用兵征讨，得地千里。';

    /** @var list<string> */
    private const UNCOVERED = [
        '“临干支年命并旺相气发用入传”的断句不能仅由本篇证明为唯一解释',
        '“更乘吉将”现取传中相关神至少一处乘吉将；原文是否要求更多位置乘吉将仍待旁证',
        '丙申日卯时子将古例取亥为昼贵，与北京实际日出日落口径不一致',
        '贵人蹉跎、坐狱、遍地贵人、两贵受克、贵作日鬼与六害等减损结构尚未实现',
    ];

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $dayStem = $facts->get('rigan');
        $dayBranch = $facts->get('rizhi');
        $heavenPlate = $facts->get('tianpan');
        $initial = $facts->get('sanchuan0');
        $middle = $facts->get('sanchuan1');
        $final = $facts->get('sanchuan2');

        if (! is_int($dayStem) || ! is_int($dayBranch) || ! is_array($heavenPlate)
            || ! is_int($initial) || ! is_int($middle) || ! is_int($final)) {
            return null;
        }

        $lu = self::DAY_STEM_LU[$dayStem] ?? null;
        $ma = self::TRAVEL_HORSE[$dayBranch] ?? null;
        $guiRen = $this->resolveGuiRen($facts);
        $dayStemLodging = $facts->stemLodgingBranch($dayStem);

        if ($lu === null || $ma === null || $guiRen === null || ! is_int($dayStemLodging)) {
            return null;
        }

        $positions = $this->collectPositions($facts, $heavenPlate, $dayStemLodging, $dayBranch, [$initial, $middle, $final]);
        $targets = ['lu' => $lu, 'ma' => $ma, 'gui_ren' => $guiRen];
        $present = [];

        foreach ($targets as $name => $branch) {
            $present[$name] = array_keys(array_filter(
                $positions,
                fn (int $positionBranch): bool => $positionBranch === $branch,
            ));
        }

        $allPresent = array_all($present, fn (array $hits): bool => $hits !== []);
        $initialQualifies = in_array($initial, $targets, true) && $facts->isBranchWangOrXiang($initial);
        $transmissionGenerals = [];

        foreach (['initial' => $initial, 'middle' => $middle, 'final' => $final] as $position => $branch) {
            if (! in_array($branch, $targets, true)) {
                continue;
            }

            $general = $facts->generalRidingBranch($branch);
            if (in_array($general, self::AUSPICIOUS_GENERALS, true)) {
                $transmissionGenerals[] = compact('position', 'branch', 'general');
            }
        }

        if (! $allPresent || ! $initialQualifies || $transmissionGenerals === []) {
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
                'terms' => [
                    'lu' => ['branch' => $lu, 'positions' => $present['lu']],
                    'ma' => ['branch' => $ma, 'positions' => $present['ma']],
                    'gui_ren' => ['branch' => $guiRen, 'positions' => $present['gui_ren']],
                ],
                'initial' => ['branch' => $initial, 'wang_xiang' => true],
                'auspicious_transmission_terms' => $transmissionGenerals,
                'seasonal_period' => $facts->seasonalPeriod(),
                'uncovered' => self::UNCOVERED,
            ],
        );
    }

    /**
     * @param  array<int, mixed>  $heavenPlate
     * @param  list<int>  $transmissions
     * @return array<string, int>
     */
    private function collectPositions(PanFacts $facts, array $heavenPlate, int $dayLodging, int $dayBranch, array $transmissions): array
    {
        $positions = [
            'day_upper' => $heavenPlate[$dayLodging],
            'branch_upper' => $heavenPlate[$dayBranch],
            'initial' => $transmissions[0],
            'middle' => $transmissions[1],
            'final' => $transmissions[2],
        ];

        foreach (['nianming' => 'ming_upper', 'xingnian' => 'xingnian_upper'] as $factName => $positionName) {
            $ground = $facts->get($factName);
            if (is_int($ground) && isset($heavenPlate[$ground]) && is_int($heavenPlate[$ground])) {
                $positions[$positionName] = $heavenPlate[$ground];
            }
        }

        return $positions;
    }

    private function resolveGuiRen(PanFacts $facts): ?int
    {
        $dayStem = $facts->get('rigan');
        $period = $facts->get('guirenPeriod');

        if (! is_int($dayStem) || ! in_array($period, ['day', 'night'], true)) {
            return null;
        }

        $table = [
            [1, 7], [0, 8], [11, 9], [11, 9], [1, 7],
            [0, 8], [1, 7], [6, 2], [5, 3], [5, 3],
        ];

        return $table[$dayStem][$period === 'day' ? 0 : 1] ?? null;
    }
}
