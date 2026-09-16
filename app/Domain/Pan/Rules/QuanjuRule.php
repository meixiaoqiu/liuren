<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/**
 * 文件作用：判断第58课全局课；五种具体格由独立 structure.* 规则展示。
 * 冻结口径：四组完整三合局任一入三传，或三传全部属于辰戌丑未四季土，即成全局课。
 */
final class QuanjuRule implements PanRule
{
    public const RULE_CODE = 'lesson.quanju';
    public const NAME = '全局课';
    public const GROUP = '六十四课';
    public const DESCRIPTION = '三传完整构成申子辰、寅午戌、亥卯未、巳酉丑任一三合局，或三传全部属于辰戌丑未四季土。';
    public const GUA = '大畜';
    public const GUA_SYMBOL = '䷙';
    public const XIANG = '三方会合，得成秀气。吉事必成，凶事难弃。尊长恩荣，常人财喜。利合婚姻，谋为大利。';

    private const UNCOVERED = [
        '“三合犯杀”要求一传与干支上神作刑、冲、破、害；当前公共地支关系尚未完整提供“刑”，故不以残缺的冲破害代替',
        '“火并火鬼”“金并血支”“木并木怪”及后合、玄武等“并”法尚未冻结统一作用域',
        '人物年命相关的稼穑细断及从革不革等复合判断尚未完整程序化',
    ];

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function definition(): array
    {
        return [
            'description' => self::DESCRIPTION,
            'xiang' => self::XIANG,
            'foundations' => [
                ['code' => 'complete_sanhe', 'title' => '三传完整三合', 'description' => '三传完整构成申子辰水局、寅午戌火局、亥卯未木局、巳酉丑金局之一。'],
                ['code' => 'all_season_earth', 'title' => '三传皆四季土', 'description' => '三传每一传都属于辰、戌、丑、未；不要求四支全部出现，也不额外要求三传互异。'],
            ],
            'judgments' => [
                ['code' => 'sanhe_forward_reverse', 'label' => '顺逆三合', 'description' => '前四格另判顺三合或逆三合；顺主理势自然，逆主事多乖违。'],
                ['code' => 'grid_seasonal_state', 'label' => '局五行旺衰', 'description' => '记录局五行在当前时令的旺相休囚死。'],
                ['code' => 'initial_seasonal_state', 'label' => '初传旺衰', 'description' => '初传有气、无气与整个局五行旺衰分开判断。'],
                ['code' => 'liuhe_assists_grid', 'label' => '六合助局', 'description' => '一传与干支上神六合，或三传所乘天将见六合，主人相助成合。'],
            ],
        ];
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $grid = QuanjuSupport::classify($facts);
        if ($grid === null) {
            return null;
        }

        $foundation = $grid['type'] === 'jiase'
            ? [
                'code' => 'all_season_earth',
                'title' => '三传皆四季土',
                'description' => '三传每一传都属于辰、戌、丑、未。',
                'matched' => true,
                'evidence' => QuanjuSupport::detail($grid),
            ]
            : [
                'code' => 'complete_sanhe',
                'title' => '三传完整三合',
                'description' => '三传完整构成四组三合局之一。',
                'matched' => true,
                'evidence' => QuanjuSupport::detail($grid),
            ];

        return new RuleMatch(
            code: self::RULE_CODE,
            name: self::NAME,
            group: self::GROUP,
            description: self::DESCRIPTION,
            gua: self::GUA,
            guaSymbol: self::GUA_SYMBOL,
            xiang: self::XIANG,
            evidence: [
                'foundations' => [$foundation],
                'judgments' => QuanjuSupport::judgments($facts, $grid),
                'uncovered' => self::UNCOVERED,
                'grid_slug' => $grid['slug'],
                'grid_name' => $grid['name'],
                'grid_element' => $grid['element_name'],
                'transmissions' => $grid['transmissions'],
                'direction' => QuanjuSupport::direction($grid),
            ],
        );
    }
}
