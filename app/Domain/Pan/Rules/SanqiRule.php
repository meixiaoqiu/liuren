<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：判断占日所在六甲旬的旬奇进入三传所成的三奇课。 */
final class SanqiRule implements PanRule
{
    /** @var array<int, int> 六旬依次取丑、丑、子、子、亥、亥为旬奇。 */
    protected const XUN_WONDERS = [1, 1, 0, 0, 11, 11];

    /** @var array<int, int> 甲至癸十日所用日奇。 */
    protected const DAY_WONDERS = [6, 5, 4, 3, 2, 1, 7, 8, 9, 10];

    protected const RULE_CODE = 'lesson.sanqi';

    protected const NAME = '三奇课';

    protected const GROUP = '六十四课';

    protected const DESCRIPTION = '占日所在六甲旬的旬奇发用、入于中传或末传；日奇作为课内等级依据。';

    protected const GUA = '豫';

    protected const GUA_SYMBOL = '䷏';

    protected const XIANG = '万事和合，千殃解除。婚求淑女，孕育贤儿。士有奇遇，病获良医。纵乘恶将，凶去通吉。';

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $stem = $facts->get('rigan');
        $branch = $facts->get('rizhi');
        $transmissions = $this->transmissions($facts);

        if (! is_int($stem) || ! is_int($branch) || $transmissions === null) {
            return null;
        }

        $dayIndex = $facts->sexagenaryDayIndex();

        if ($dayIndex === null) {
            return null;
        }

        $xunIndex = intdiv($dayIndex, 10);
        $xunWonder = self::XUN_WONDERS[$xunIndex];
        $dayWonder = self::DAY_WONDERS[$stem] ?? null;
        $xunPositions = $this->matchingPositions($transmissions, $xunWonder);
        $dayPositions = is_int($dayWonder) ? $this->matchingPositions($transmissions, $dayWonder) : [];

        if ($xunPositions === []) {
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
                'day_index' => $dayIndex,
                'xun_index' => $xunIndex,
                'xun_head_index' => $xunIndex * 10,
                'xun_wonder' => $xunWonder,
                'day_wonder' => $dayWonder,
                'transmissions' => $transmissions,
                'xun_wonder_positions' => $xunPositions,
                'day_wonder_positions' => $dayPositions,
                'both_wonders_present' => $xunPositions !== [] && $dayPositions !== [],
                'three_wonders_linked' => $this->containsHaiZiChou($transmissions),
            ],
        );
    }

    /** @return list<int>|null */
    private function transmissions(PanFacts $facts): ?array
    {
        $transmissions = [
            $facts->get('sanchuan0'),
            $facts->get('sanchuan1'),
            $facts->get('sanchuan2'),
        ];

        return count(array_filter($transmissions, 'is_int')) === 3 ? $transmissions : null;
    }

    /** @param list<int> $transmissions @return list<int> */
    private function matchingPositions(array $transmissions, int $wonder): array
    {
        $positions = [];

        foreach ($transmissions as $position => $branch) {
            if ($branch === $wonder) {
                $positions[] = $position;
            }
        }

        return $positions;
    }

    /** @param list<int> $transmissions */
    private function containsHaiZiChou(array $transmissions): bool
    {
        $distinct = array_values(array_unique($transmissions));
        sort($distinct);

        return $distinct === [0, 1, 11];
    }
}
