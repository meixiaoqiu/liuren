<?php

namespace App\Domain\Pan\Facts;

use App\Data\PanResult;
use com\tyme\solar\SolarTime;

final readonly class PanFacts
{
    /** @var array<int, int> */
    private const STEM_ELEMENTS = [0, 0, 1, 1, 2, 2, 3, 3, 4, 4];

    /** @var array<int, int> */
    private const BRANCH_ELEMENTS = [4, 2, 0, 0, 2, 1, 1, 2, 3, 3, 2, 4];

    /** @var array<int, int> */
    private const STEM_LODGING_BRANCHES = [2, 4, 5, 7, 5, 7, 8, 10, 11, 1];

    /** @var array<int, list<int>> */
    private const SEASON_WANG_XIANG_ELEMENTS = [
        0 => [4, 0],
        1 => [2, 3],
        2 => [0, 1],
        3 => [0, 1],
        4 => [2, 3],
        5 => [1, 2],
        6 => [1, 2],
        7 => [2, 3],
        8 => [3, 4],
        9 => [3, 4],
        10 => [2, 3],
        11 => [4, 0],
    ];

    public function __construct(private PanResult $pan) {}

    public static function from(PanResult $pan): self
    {
        return new self($pan);
    }

    public function get(string $key): mixed
    {
        return $this->pan->get($key);
    }

    /** @return array<string, mixed> */
    public function calculationTrace(): array
    {
        $trace = $this->pan->get('calculationTrace');

        return is_array($trace) ? $trace : [];
    }

    public function hasPlatePattern(string $pattern): bool
    {
        return in_array($pattern, $this->calculationTrace()['plate_patterns'] ?? [], true);
    }

    public function hasLessonPattern(string $pattern): bool
    {
        return in_array($pattern, $this->calculationTrace()['lesson_patterns'] ?? [], true);
    }

    public function chuchuanMethod(): ?string
    {
        $trace = $this->calculationTrace()['initial_transmission'] ?? [];

        if (($trace['recorded'] ?? false) !== true) {
            return null;
        }

        $method = $trace['method'] ?? null;

        return is_string($method) ? $method : null;
    }

    public function sanchuanMethod(string $stage): ?string
    {
        $trace = $this->calculationTrace()[$stage.'_transmission'] ?? [];

        if (($trace['recorded'] ?? false) !== true) {
            return null;
        }

        $method = $trace['method'] ?? null;

        return is_string($method) ? $method : null;
    }

    /** @return array<string, mixed> */
    public function chuchuanEvidence(): array
    {
        $evidence = $this->calculationTrace()['initial_transmission']['evidence'] ?? [];

        return is_array($evidence) ? $evidence : [];
    }

    public function isStemWangOrXiang(int $stem): bool
    {
        $element = $this->stemElement($stem);

        return $element !== null && $this->isElementWangOrXiang($element);
    }

    public function isBranchWangOrXiang(int $branch): bool
    {
        $element = $this->branchElement($branch);

        return $element !== null && $this->isElementWangOrXiang($element);
    }

    public function stemElement(int $stem): ?int
    {
        return self::STEM_ELEMENTS[$stem] ?? null;
    }

    public function branchElement(int $branch): ?int
    {
        return self::BRANCH_ELEMENTS[$branch] ?? null;
    }

    public function isDayWealthBranch(int $branch): bool
    {
        $stem = $this->get('rigan');
        $stemElement = is_int($stem) ? $this->stemElement($stem) : null;
        $branchElement = $this->branchElement($branch);

        return $stemElement !== null
            && $branchElement !== null
            && $branchElement === ($stemElement + 2) % 5;
    }

    public function stemSeasonalStrength(int $stem): ?string
    {
        $element = $this->stemElement($stem);

        return $element === null ? null : $this->seasonalStrength($element);
    }

    public function branchSeasonalStrength(int $branch): ?string
    {
        $element = $this->branchElement($branch);

        return $element === null ? null : $this->seasonalStrength($element);
    }

    /** @return array{wang: int, xiang: int}|null */
    public function wangXiangElements(): ?array
    {
        $period = $this->seasonalPeriod();

        if ($period !== null) {
            return ['wang' => $period['wang'], 'xiang' => $period['xiang']];
        }

        $monthBranch = $this->get('yuezhi');
        $elements = is_int($monthBranch) ? (self::SEASON_WANG_XIANG_ELEMENTS[$monthBranch] ?? null) : null;

        return $elements === null ? null : ['wang' => $elements[0], 'xiang' => $elements[1]];
    }

    /**
     * 《六壬大全》以四立划分四时，并规定每一季结束前十八日土旺。
     * 古籍未明定十八日的时刻精度；此处统一按下一个四立的精确交节时刻倒推十八个整日。
     *
     * @return array{key: string, name: string, wang: int, xiang: int, starts_at: string, ends_at: string, implementation: string}|null
     */
    public function seasonalPeriod(): ?array
    {
        $value = $this->get('calculationTime');

        if (! is_string($value)) {
            return null;
        }

        $parts = date_parse($value);

        if (($parts['error_count'] ?? 1) !== 0) {
            return null;
        }

        $current = SolarTime::fromYmdHms(
            $parts['year'],
            $parts['month'],
            $parts['day'],
            $parts['hour'],
            $parts['minute'],
            $parts['second'],
        );
        $term = $current->getTerm();
        $nextFourLi = $term;

        do {
            $nextFourLi = $nextFourLi->next(1);
        } while (! in_array($nextFourLi->getIndex(), [3, 9, 15, 21], true));

        $nextFourLiTime = $nextFourLi->getJulianDay()->getSolarTime();
        $soilStartsAt = $nextFourLiTime->next(-18 * 86400);

        if (! $current->isBefore($soilStartsAt)) {
            return [
                'key' => 'soil',
                'name' => '四季土旺',
                'wang' => 2,
                'xiang' => 3,
                'starts_at' => self::formatSolarTime($soilStartsAt),
                'ends_at' => self::formatSolarTime($nextFourLiTime),
                'implementation' => '下一个四立精确交节时刻前推十八个整日',
            ];
        }

        [$key, $name, $wang, $xiang] = match (true) {
            $term->getIndex() >= 3 && $term->getIndex() < 9 => ['spring', '春季', 0, 1],
            $term->getIndex() >= 9 && $term->getIndex() < 15 => ['summer', '夏季', 1, 2],
            $term->getIndex() >= 15 && $term->getIndex() < 21 => ['autumn', '秋季', 3, 4],
            default => ['winter', '冬季', 4, 0],
        };
        $seasonStartsAt = $nextFourLi->next(-6)->getJulianDay()->getSolarTime();

        return [
            'key' => $key,
            'name' => $name,
            'wang' => $wang,
            'xiang' => $xiang,
            'starts_at' => self::formatSolarTime($seasonStartsAt),
            'ends_at' => self::formatSolarTime($soilStartsAt),
            'implementation' => '四立交节至本季土旺开始时刻',
        ];
    }

    public function generalRidingBranch(int $branch): ?int
    {
        $tianpan = $this->get('tianpan');
        $generals = $this->get('tianjiang');

        if (! is_array($tianpan) || ! is_array($generals)) {
            return null;
        }

        $position = array_search($branch, $tianpan, true);
        $general = $position === false ? null : ($generals[$position] ?? null);

        return is_int($general) ? $general : null;
    }

    public function generalAtGroundPosition(int $ground): ?int
    {
        $generals = $this->get('tianjiang');
        $general = is_array($generals) ? ($generals[$ground] ?? null) : null;

        return is_int($general) ? $general : null;
    }

    public function stemLodgingBranch(int $stem): ?int
    {
        return self::STEM_LODGING_BRANCHES[$stem] ?? null;
    }

    public function sexagenaryDayIndex(): ?int
    {
        $stem = $this->get('rigan');
        $branch = $this->get('rizhi');

        if (! is_int($stem) || ! is_int($branch)) {
            return null;
        }

        for ($index = 0; $index < 60; $index++) {
            if ($index % 10 === $stem && $index % 12 === $branch) {
                return $index;
            }
        }

        return null;
    }

    public function dayXunIndex(): ?int
    {
        $dayIndex = $this->sexagenaryDayIndex();

        return $dayIndex === null ? null : intdiv($dayIndex, 10);
    }

    public function dayXunHeadBranch(): ?int
    {
        $xunIndex = $this->dayXunIndex();

        return $xunIndex === null ? null : [0, 10, 8, 6, 4, 2][$xunIndex];
    }

    public function isNoblemanMovingForward(): bool
    {
        $generals = $this->get('tianjiang');

        return is_array($generals)
            && isset($generals[0], $generals[1])
            && is_int($generals[0])
            && is_int($generals[1])
            && ($generals[0] + 1) % 12 === $generals[1];
    }

    public function noblemanGroundPosition(): ?int
    {
        $generals = $this->get('tianjiang');
        $position = is_array($generals) ? array_search(0, $generals, true) : false;

        return $position === false ? null : $position;
    }

    public function isGroundPositionRidingNoblemanFrontGeneral(int $ground): bool
    {
        return $this->noblemanFrontGeneralRankAtGroundPosition($ground) !== null;
    }

    public function noblemanFrontGeneralRankAtGroundPosition(int $ground): ?int
    {
        $general = $this->generalAtGroundPosition($ground);

        return is_int($general) && in_array($general, [1, 2, 3, 4, 5], true)
            ? $general
            : null;
    }

    private function isElementWangOrXiang(int $element): bool
    {
        return in_array($this->seasonalStrength($element), ['旺', '相'], true);
    }

    private function seasonalStrength(int $element): ?string
    {
        $wangXiang = $this->wangXiangElements();

        return match ($element) {
            $wangXiang['wang'] ?? null => '旺',
            $wangXiang['xiang'] ?? null => '相',
            default => null,
        };
    }

    private static function formatSolarTime(SolarTime $time): string
    {
        return sprintf(
            '%04d-%02d-%02d %02d:%02d:%02d',
            $time->getYear(),
            $time->getMonth(),
            $time->getDay(),
            $time->getHour(),
            $time->getMinute(),
            $time->getSecond(),
        );
    }
}
