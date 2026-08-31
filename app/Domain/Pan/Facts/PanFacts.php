<?php

namespace App\Domain\Pan\Facts;

use App\Data\PanResult;

final readonly class PanFacts
{
    /** @var array<int, int> */
    private const STEM_ELEMENTS = [0, 0, 1, 1, 2, 2, 3, 3, 4, 4];

    /** @var array<int, int> */
    private const BRANCH_ELEMENTS = [4, 2, 0, 0, 2, 1, 1, 2, 3, 3, 2, 4];

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
        return isset(self::STEM_ELEMENTS[$stem])
            && $this->isElementWangOrXiang(self::STEM_ELEMENTS[$stem]);
    }

    public function isBranchWangOrXiang(int $branch): bool
    {
        return isset(self::BRANCH_ELEMENTS[$branch])
            && $this->isElementWangOrXiang(self::BRANCH_ELEMENTS[$branch]);
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

    private function isElementWangOrXiang(int $element): bool
    {
        $monthBranch = $this->get('yuezhi');

        return is_int($monthBranch)
            && in_array($element, self::SEASON_WANG_XIANG_ELEMENTS[$monthBranch] ?? [], true);
    }
}
