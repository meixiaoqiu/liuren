<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：判断占日所在六甲旬的旬首地支进入三传所成的六仪课。 */
final class LiuyiRule implements PanRule
{
    /** @var array<int, int> 子至亥各日支所对应的支仪。 */
    protected const BRANCH_INSTRUMENTS = [6, 5, 4, 3, 2, 1, 7, 8, 9, 10, 11, 0];

    protected const RULE_CODE = 'lesson.liuyi';

    protected const NAME = '六仪课';

    protected const GROUP = '六十四课';

    protected const DESCRIPTION = '占日所在六甲旬的旬首地支发用、入于中传或末传；支仪作为课内等级依据。';

    protected const GUA = '兑';

    protected const GUA_SYMBOL = '䷹';

    protected const XIANG = '兆多喜庆，求旺相宜。罪逢赦宥，病遇良医。投书见喜，干贵逢时。杀神回避，喜转愁眉。';

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $dayBranch = $facts->get('rizhi');
        $dayIndex = $facts->sexagenaryDayIndex();
        $xunIndex = $facts->dayXunIndex();
        $xunInstrument = $facts->dayXunHeadBranch();
        $transmissions = $this->transmissions($facts);

        if (! is_int($dayBranch)
            || $dayIndex === null
            || $xunIndex === null
            || $xunInstrument === null
            || $transmissions === null) {
            return null;
        }

        $branchInstrument = self::BRANCH_INSTRUMENTS[$dayBranch] ?? null;
        $xunPositions = $this->matchingPositions($transmissions, $xunInstrument);
        $branchPositions = is_int($branchInstrument)
            ? $this->matchingPositions($transmissions, $branchInstrument)
            : [];

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
                'xun_instrument' => $xunInstrument,
                'branch_instrument' => $branchInstrument,
                'transmissions' => $transmissions,
                'xun_instrument_positions' => $xunPositions,
                'branch_instrument_positions' => $branchPositions,
                'both_instruments_present' => $branchPositions !== [],
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
    private function matchingPositions(array $transmissions, int $instrument): array
    {
        $positions = [];

        foreach ($transmissions as $position => $branch) {
            if ($branch === $instrument) {
                $positions[] = $position;
            }
        }

        return $positions;
    }
}
