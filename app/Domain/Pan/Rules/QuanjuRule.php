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
        '“值旺相老凶少吉、囚死少凶老吉”等老少细断尚未建立统一年龄边界',
        '“用有气孕生男、用无气孕生女”的胎孕细断尚未结构化',
        '“经求利／坐守利、求财传财、官易就、君子小人”等问事分类细断尚未建立统一上下文',
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
                [
                    'code' => 'quanju_any_grid',
                    'title' => '五格任一成立',
                    'description' => '以下任一结构成立即成全局课：申子辰为润下格；寅午戌为炎上格；亥卯未为曲直格；巳酉丑为从革格；三传全部属于辰、戌、丑、未为稼穑格。',
                ],
            ],
            'judgments' => [
                ['code' => 'sanhe_forward', 'effect' => 'increase', 'label' => '顺三合', 'description' => '前四格三传按生→旺→墓循环顺行，理势自然。'],
                ['code' => 'sanhe_reverse', 'effect' => 'reduce', 'label' => '逆三合', 'description' => '前四格三传逆生→旺→墓循环，事主乖违。'],
                ['code' => 'grid_seasonal_state_wang', 'effect' => 'neutral', 'label' => '局五行时令旺', 'description' => '全局所属五行在当前四立／土旺十八日口径下为旺；只记录状态，不脱离具体问事与对象统一判吉凶。'],
                ['code' => 'grid_seasonal_state_xiang', 'effect' => 'neutral', 'label' => '局五行时令相', 'description' => '全局所属五行在当前四立／土旺十八日口径下为相；只记录状态，不脱离具体问事与对象统一判吉凶。'],
                ['code' => 'grid_seasonal_state_xiu', 'effect' => 'neutral', 'label' => '局五行时令休', 'description' => '全局所属五行在当前四立／土旺十八日口径下为休。'],
                ['code' => 'grid_seasonal_state_qiu', 'effect' => 'neutral', 'label' => '局五行时令囚', 'description' => '全局所属五行在当前四立／土旺十八日口径下为囚；只记录状态，不脱离具体问事与对象统一判吉凶。'],
                ['code' => 'grid_seasonal_state_si', 'effect' => 'neutral', 'label' => '局五行时令死', 'description' => '全局所属五行在当前四立／土旺十八日口径下为死；只记录状态，不脱离具体问事与对象统一判吉凶。'],
                ['code' => 'initial_seasonal_state_wang', 'effect' => 'neutral', 'label' => '初传时令旺', 'description' => '初传在当前时令为旺；只记录有气状态，胎孕等细断另待上下文实现。'],
                ['code' => 'initial_seasonal_state_xiang', 'effect' => 'neutral', 'label' => '初传时令相', 'description' => '初传在当前时令为相；只记录有气状态，胎孕等细断另待上下文实现。'],
                ['code' => 'initial_seasonal_state_xiu', 'effect' => 'neutral', 'label' => '初传时令休', 'description' => '初传在当前时令为休；与整个局五行旺衰分开判断。'],
                ['code' => 'initial_seasonal_state_qiu', 'effect' => 'neutral', 'label' => '初传时令囚', 'description' => '初传在当前时令为囚；只记录无气状态，胎孕等细断另待上下文实现。'],
                ['code' => 'initial_seasonal_state_si', 'effect' => 'neutral', 'label' => '初传时令死', 'description' => '初传在当前时令为死；只记录无气状态，胎孕等细断另待上下文实现。'],
                ['code' => 'liuhe_assists_grid', 'effect' => 'increase', 'label' => '六合助局', 'description' => '一传与干支上神六合，或三传所乘天将见六合，主人相助成合。'],

                ['code' => 'runxia_wood_day_shengqi', 'effect' => 'increase', 'label' => '润下·木日得生气', 'description' => '润下水局生木日。'],
                ['code' => 'runxia_metal_day_daoqi', 'effect' => 'reduce', 'label' => '润下·金日为盗气', 'description' => '金日生润下水局，日干之气外泄。'],

                ['code' => 'yanshang_earth_day_shengqi', 'effect' => 'increase', 'label' => '炎上·土日得生气', 'description' => '炎上火局生土日。'],
                ['code' => 'yanshang_wood_day_daoqi', 'effect' => 'reduce', 'label' => '炎上·木日为盗气', 'description' => '木日生炎上火局，日干之气外泄。'],
                ['code' => 'yanshang_gengxin_kill', 'effect' => 'reduce', 'label' => '炎上·庚辛日带杀', 'description' => '庚辛属金，受炎上火局所克。'],
                ['code' => 'yanshang_rengui_zimugui', 'effect' => 'reduce', 'label' => '炎上·壬癸日子母鬼', 'description' => '壬癸属水，虽以火为财，火又生土反制水，传统称子母鬼。'],
                ['code' => 'yanshang_xu_on_yin', 'effect' => 'reduce', 'label' => '炎上·戌加寅：墓临生', 'description' => '炎上格见天盘戌加临地盘寅，为墓临生。'],
                ['code' => 'yanshang_wu_on_xu', 'effect' => 'reduce', 'label' => '炎上·午加戌：入墓', 'description' => '炎上格见天盘午加临地盘戌，为火入墓。'],

                ['code' => 'quzhi_ji_rooted', 'effect' => 'increase', 'label' => '曲直·己日根固', 'description' => '曲直木局见己日，传统称根固。'],
                ['code' => 'quzhi_ding_withered', 'effect' => 'reduce', 'label' => '曲直·丁日枝枯', 'description' => '丁火泄木，曲直格传统称枝枯。'],
                ['code' => 'quzhi_xin_material', 'effect' => 'increase', 'label' => '曲直·辛日成器', 'description' => '辛金裁木，曲直格传统取成器之义。'],
                ['code' => 'quzhi_wei_on_hai', 'effect' => 'neutral', 'label' => '曲直·未加亥：直', 'description' => '未加亥，传统取“直”义。'],
                ['code' => 'quzhi_hai_on_wei', 'effect' => 'neutral', 'label' => '曲直·亥加未：曲', 'description' => '亥加未，传统取“曲”义。'],
                ['code' => 'quzhi_mao_on_hai', 'effect' => 'neutral', 'label' => '曲直·卯加亥：先曲后直', 'description' => '卯加亥，主始难终易。'],
                ['code' => 'quzhi_mao_on_wei', 'effect' => 'neutral', 'label' => '曲直·卯加未：先直后曲', 'description' => '卯加未，主有始无终。'],

                ['code' => 'congge_water_day_shengqi', 'effect' => 'increase', 'label' => '从革·水日得生气', 'description' => '从革金局生水日。'],
                ['code' => 'congge_earth_day_daoqi', 'effect' => 'reduce', 'label' => '从革·土日为盗气', 'description' => '土日生从革金局，日干之气外泄。'],

                ['code' => 'jiase_wuji_harder', 'effect' => 'reduce', 'label' => '稼穑·戊己日更艰难', 'description' => '《订讹》谓稼穑占主沉滞，戊己日更属艰难。'],
                ['code' => 'jiase_rengui_release', 'effect' => 'resolve', 'label' => '稼穑·壬癸日脱难', 'description' => '《订讹》称壬癸日为脱难杀。'],
                ['code' => 'jiase_thunder_god', 'effect' => 'resolve', 'label' => '稼穑·雷神解滞', 'description' => '太冲卯乘六合天将，按《订讹》称雷神，主变化。'],
            ],
        ];
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $grid = QuanjuSupport::classify($facts);
        if ($grid === null) {
            return null;
        }

        $foundation = [
            'code' => 'quanju_any_grid',
            'title' => '五格任一成立',
            'description' => '申子辰、寅午戌、亥卯未、巳酉丑任一完整三合局，或三传皆四季土，即成全局课。',
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
