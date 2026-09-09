<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\BranchRelations;
use App\Domain\Pan\Facts\PanFacts;

/**
 * 文件作用：按《观月经》德孕段判断夫妻行年天干五合、地支六合所成的德孕格。
 *
 * 原文依据：
 *  - 《观月经》德孕段："夫年四十九，岁在甲寅落。妻年三十四，己亥……甲己合，寅与亥合……不论三传事"。
 *  - 《心镜》"德孕行年课十干，还如甲己类同攒"。
 *  - 《曾门》"夫妻之年，甲己相合，更立德乡，子孙繁昌"。
 *
 * 本实现仅采用"行年天干五合 + 行年地支六合"两条结构条件，不读三传、不查本命五行、不核各乘本命旺气。
 * 上述额外条件见课经笔记，本轮不作为德孕格的通用必要条件，原因详见 docs/课经/24-繁昌课.md。
 */
final class DeYunRule extends FanchangGridRule
{
    /**
     * 天干五合显式表（甲己、乙庚、丙辛、丁壬、戊癸）。
     * 测试必须使用此显式表断言五合成立，不得调用本类或其他生产方法生成预期值。
     *
     * @var list<array{0: int, 1: int}>
     */
    private const STEM_COMBINES = [
        [0, 5], // 甲己
        [1, 6], // 乙庚
        [2, 7], // 丙辛
        [3, 8], // 丁壬
        [4, 9], // 戊癸
    ];

    protected const SLUG = 'de_yun';

    protected const NAME = '德孕格';

    protected const DESCRIPTION = '夫妻行年天干五合、地支六合。本实现按《观月经》德孕段"不论三传事"立论，不读取三传。';

    protected function evaluate(PanFacts $facts): ?string
    {
        $data = self::fanchangData($facts);

        if ($data === null) {
            return null;
        }

        $hGan = $data['fu_xingnian_gan'];
        $hZhi = $data['fu_xingnian'];
        $wGan = $data['qi_xingnian_gan'];
        $wZhi = $data['qi_xingnian'];

        $stemPair = self::findPair(self::STEM_COMBINES, $hGan, $wGan);
        $branchPair = BranchRelations::liuhePair($hZhi, $wZhi);

        if ($stemPair === null || $branchPair === null) {
            return null;
        }

        $stems = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];
        $branches = self::BRANCH_NAMES;

        $hGz = $stems[$hGan].$branches[$hZhi];
        $wGz = $stems[$wGan].$branches[$wZhi];
        $stemName = $stems[$stemPair[0]].$stems[$stemPair[1]];
        $branchName = $branches[$branchPair[0]].$branches[$branchPair[1]];

        return sprintf(
            '夫行年%s、妻行年%s；%s天干五合、%s地支六合。本实现采用《观月经》口径，不论三传。',
            $hGz,
            $wGz,
            $stemName,
            $branchName,
        );
    }

    /**
     * 在显式表中查找 (a, b) 是否构成配对（顺序无关）。命中时返回表中的原始有序对。
     *
     * @param  list<array{0: int, 1: int}>  $table
     * @return array{0: int, 1: int}|null
     */
    private static function findPair(array $table, int $a, int $b): ?array
    {
        foreach ($table as [$x, $y]) {
            if (($a === $x && $b === $y) || ($a === $y && $b === $x)) {
                return [$x, $y];
            }
        }

        return null;
    }
}
