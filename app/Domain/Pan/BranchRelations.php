<?php

namespace App\Domain\Pan;

/**
 * 文件作用：集中维护地支六冲、六破、六合与三合关系，供排盘规则和展示共同使用。
 * 地支均采用子=0、丑=1、……、亥=11的项目统一索引。
 */
final class BranchRelations
{
    /** @var list<int> 每一地支所冲之支。 */
    public const CHONG = [6, 7, 8, 9, 10, 11, 0, 1, 2, 3, 4, 5];

    /** @var list<int> 每一地支所破之支。 */
    public const PO = [9, 4, 11, 6, 1, 8, 3, 10, 5, 0, 7, 2];

    /** @var list<array{0:int,1:int}> 规范六合对。 */
    public const LIUHE_PAIRS = [
        [0, 1], [2, 11], [3, 10], [4, 9], [5, 8], [6, 7],
    ];

    /** @var list<list<int>> 规范升序三合局。 */
    public const SANHE_TRIPLES = [
        [0, 4, 8], [1, 5, 9], [2, 6, 10], [3, 7, 11],
    ];

    public static function clashOf(int $branch): ?int
    {
        return self::CHONG[$branch] ?? null;
    }

    public static function breakOf(int $branch): ?int
    {
        return self::PO[$branch] ?? null;
    }

    public static function isLiuhe(int $a, int $b): bool
    {
        return self::liuhePair($a, $b) !== null;
    }

    /** @return array{0:int,1:int}|null 返回规范顺序的六合对。 */
    public static function liuhePair(int $a, int $b): ?array
    {
        foreach (self::LIUHE_PAIRS as $pair) {
            if (($a === $pair[0] && $b === $pair[1]) || ($a === $pair[1] && $b === $pair[0])) {
                return $pair;
            }
        }

        return null;
    }

    public static function isSanhe(int $a, int $b, int $c): bool
    {
        return self::sanheTriple($a, $b, $c) !== null;
    }

    /** @return list<int>|null 返回规范升序的三合局。 */
    public static function sanheTriple(int $a, int $b, int $c): ?array
    {
        $branches = [$a, $b, $c];
        sort($branches);

        return in_array($branches, self::SANHE_TRIPLES, true) ? $branches : null;
    }

    /** 两支是否属于同一三合局且并非同一支。 */
    public static function shareSanheGroup(int $a, int $b): bool
    {
        if ($a === $b) {
            return false;
        }

        foreach (self::SANHE_TRIPLES as $triple) {
            if (in_array($a, $triple, true) && in_array($b, $triple, true)) {
                return true;
            }
        }

        return false;
    }
}
