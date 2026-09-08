<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：为繁昌课下的德孕、旺孕两格提供夫妻行年读取与地支三合判断。德孕按《观月经》口径（行年干支相合），旺孕按行年地支三合且俱得季节旺相。 */
trait FanchangSupport
{
    /** @var list<string> */
    protected const BRANCH_NAMES = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];

    /** @var array<int, list<int>> 地支三合局（寅午戌火、亥卯未木、巳酉丑金、申子辰水）。 */
    protected const SAN_HE_JU = [
        [2, 6, 10],
        [11, 3, 7],
        [5, 9, 1],
        [8, 0, 4],
    ];

    /** 地支三合（同类异位，即同属一个三合局且不相等）。 */
    protected static function branchesTripleCombine(int $a, int $b): bool
    {
        if ($a === $b) {
            return false;
        }

        foreach (self::SAN_HE_JU as $ju) {
            if (in_array($a, $ju, true) && in_array($b, $ju, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 从占测上下文中读取占者与配偶，并按性别归为夫命、妻命。
     *
     * 两格（德孕、旺孕）都只读取行年干支与性别；本命 nianming 可选，
     * 缺失时本方法仍返回结构化数据，由具体格按需取用。
     *
     * @return array{
     *     fu_nianming: ?int, fu_xingnian: int, fu_xingnian_gan: int,
     *     qi_nianming: ?int, qi_xingnian: int, qi_xingnian_gan: int
     * }|null
     */
    protected static function fanchangData(PanFacts $facts): ?array
    {
        $querent = $facts->personByRole('querent');
        $spouse = $facts->personByRole('spouse');

        if ($querent === null || $spouse === null) {
            return null;
        }

        $husband = ($querent['gender'] ?? null) === 'male' ? $querent : $spouse;
        $wife = ($querent['gender'] ?? null) === 'female' ? $querent : $spouse;

        if (($husband['gender'] ?? null) !== 'male' || ($wife['gender'] ?? null) !== 'female') {
            return null;
        }

        $fuNianmingRaw = $husband['nianming'] ?? null;
        $qiNianmingRaw = $wife['nianming'] ?? null;
        $fuXingnian = $husband['xingnian'] ?? null;
        $fuXingnianGan = $husband['xingnian_gan'] ?? null;
        $qiXingnian = $wife['xingnian'] ?? null;
        $qiXingnianGan = $wife['xingnian_gan'] ?? null;

        // 两格只强制要求 gender + xingnian + xingnian_gan；
        // nianming 若存在则透传，缺失时返回 null，不影响德孕/旺孕判定。
        if (! is_int($fuXingnian) || ! is_int($fuXingnianGan)
            || ! is_int($qiXingnian) || ! is_int($qiXingnianGan)) {
            return null;
        }

        return [
            'fu_nianming' => is_int($fuNianmingRaw) ? $fuNianmingRaw : null,
            'fu_xingnian' => $fuXingnian,
            'fu_xingnian_gan' => $fuXingnianGan,
            'qi_nianming' => is_int($qiNianmingRaw) ? $qiNianmingRaw : null,
            'qi_xingnian' => $qiXingnian,
            'qi_xingnian_gan' => $qiXingnianGan,
        ];
    }

    /** 旺孕格成格说明；未命中返回 null。 */
    protected static function wangYunDetail(PanFacts $facts): ?string
    {
        $data = self::fanchangData($facts);

        if ($data === null) {
            return null;
        }

        if (! self::branchesTripleCombine($data['fu_xingnian'], $data['qi_xingnian'])
            || ! $facts->isBranchWangOrXiang($data['fu_xingnian'])
            || ! $facts->isBranchWangOrXiang($data['qi_xingnian'])) {
            return null;
        }

        return '夫行年'.self::BRANCH_NAMES[$data['fu_xingnian']].'、妻行年'.self::BRANCH_NAMES[$data['qi_xingnian']]
            .'，'.self::BRANCH_NAMES[$data['fu_xingnian']].'与'.self::BRANCH_NAMES[$data['qi_xingnian']]
            .'为三合（异方同类），且俱得季节旺相。';
    }
}
