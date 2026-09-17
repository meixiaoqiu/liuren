<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/**
 * 文件作用：判断第59课玄胎课，并结构化生胎、病胎、绝胎三项传统分型。
 *
 * 冻结口径：
 * 1. 主体只要求初、中、末三传全部属于四孟寅巳申亥；
 * 2. “孟神发用”不另加条件，因为初传本身已经包含在“三传俱孟”中；
 * 3. 生胎、病胎、绝胎属于课成后的分型/修证，不参与主体 matcher。
 */
final class XuantaiRule implements PanRule
{
    use LessonDefinitionDefaults;

    public const RULE_CODE = 'lesson.xuantai';

    public const NAME = '玄胎课';

    public const GROUP = '六十四课';

    public const GUA = '家人';

    public const GUA_SYMBOL = '䷤';

    public const DESCRIPTION = '初传、中传、末传全部属于寅、巳、申、亥四孟，为玄胎课。';

    public const XIANG = '三传长生，胎孕成形。官加恩爵，婚获娉婷。病讼淹滞，财利叠兴。行人敌贼，恋生不行。';

    /** @var list<int> */
    private const MENG_BRANCHES = [2, 5, 8, 11];

    /**
     * 进步长生：《订讹》作“主事速，又名病胎”。
     * 键为地盘，值为其上所加天盘。
     *
     * @var array<int, int>
     */
    private const PROGRESSIVE_RELATIONS = [
        5 => 2,  // 寅加巳
        8 => 5,  // 巳加申
        11 => 8, // 申加亥
        2 => 11, // 亥加寅
    ];

    /**
     * 退步长生：《订讹》作“主事迟，又名生胎”。
     *
     * @var array<int, int>
     */
    private const RETROGRADE_RELATIONS = [
        11 => 2, // 寅加亥
        8 => 11, // 亥加申
        5 => 8,  // 申加巳
        2 => 5,  // 巳加寅
    ];

    private const UNCOVERED = [
        '用值天后财爻、妻财值生气且胎神发用、年命见之等胎孕细断尚未程序化',
        '喜神吉将、三刑及凶将等综合神将吉凶尚未程序化',
        '父母用事或发用、子孙空亡、天后空亡与“日用休囚且天后落空”等细断尚未动态程序化',
        '病讼、行人、捕贼、老幼占病等问事分类缺少统一占事上下文，暂不做动态触发',
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
                    'code' => 'three_transmissions_all_meng',
                    'title' => '三传俱孟',
                    'description' => '初传、中传、末传必须全部属于四孟寅、巳、申、亥；正文“孟神发用”已包含在初传属于四孟之内。',
                ],
            ],
            'judgments' => [
                [
                    'code' => 'bing_tai',
                    'effect' => 'reduce',
                    'label' => '病胎：进步长生',
                    'description' => '寅加巳、巳加申、申加亥、亥加寅。《订讹》断主事速，又名病胎，怀胎有忧。',
                ],
                [
                    'code' => 'sheng_tai',
                    'effect' => 'increase',
                    'label' => '生胎：退步长生',
                    'description' => '寅加亥、亥加申、申加巳、巳加寅。《订讹》断主事迟，又名生胎，怀胎大吉。',
                ],
                [
                    'code' => 'jue_tai',
                    'effect' => 'reduce',
                    'label' => '绝胎：反吟四孟',
                    'description' => '玄胎课又见反吟，传统称绝胎；《灵觉经》明确断胎产之灾。',
                ],
                [
                    'code' => 'use_tianhou_wealth',
                    'effect' => 'increase',
                    'label' => '用值天后财爻',
                    'description' => '《六壬大全》正文单列：用神值天后且为财爻，主结偶怀胎；不与下一条强行合并为一个 AND 条件。',
                ],
                [
                    'code' => 'wealth_shengqi_taishen',
                    'effect' => 'increase',
                    'label' => '妻财值生气、胎神发用',
                    'description' => '《六壬大全》正文另列：妻财值生气，且胎神发用，主妻有孕；年命见之，遇玄胎尤的。',
                ],
                [
                    'code' => 'ri_yong_xiuqiu_tianhou_void',
                    'effect' => 'reduce',
                    'label' => '日用休囚、天后落空：玄胎不育',
                    'description' => '《六壬大全》正文：日与用休囚，又见天后落空，为玄胎不育。',
                ],
                [
                    'code' => 'good_spirits_generals',
                    'effect' => 'increase',
                    'label' => '喜神吉将',
                    'description' => '玄胎带喜神吉将，正文称利远行及经求名利，百事皆吉。',
                ],
                [
                    'code' => 'old_young_illness',
                    'effect' => 'reduce',
                    'label' => '老幼占病',
                    'description' => '老幼占病，正文取“后世投胎”之象，断凶。',
                ],
                [
                    'code' => 'three_punishments_bad_generals',
                    'effect' => 'reduce',
                    'label' => '三刑及凶将',
                    'description' => '常占遇三刑及凶将，传统断忧疑惊恐。',
                ],
                [
                    'code' => 'parents_initial',
                    'effect' => 'reduce',
                    'label' => '父母用事 / 发用',
                    'description' => '《六壬大全》正文作“父母用事”，《订讹》作“父母发用”，二者均断尊长见灾；程序尚未动态触发。',
                ],
                [
                    'code' => 'offspring_void',
                    'effect' => 'reduce',
                    'label' => '子孙空亡：玄胎不育',
                    'description' => '《订讹》称子孙空亡为玄胎不育，凡占无成，更艰子息。',
                ],
                [
                    'code' => 'tianhou_void',
                    'effect' => 'reduce',
                    'label' => '天后空亡',
                    'description' => '《订讹》断天后空亡为因孕伤母。',
                ],
            ],
        ];
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $transmissions = [];
        foreach (['sanchuan0', 'sanchuan1', 'sanchuan2'] as $key) {
            $branch = $facts->get($key);
            if (! is_int($branch) || ! in_array($branch, self::MENG_BRANCHES, true)) {
                return null;
            }
            $transmissions[] = $branch;
        }

        $tianpan = $facts->get('tianpan');
        $bingTai = self::matchesPlateRelations($tianpan, self::PROGRESSIVE_RELATIONS);
        $shengTai = self::matchesPlateRelations($tianpan, self::RETROGRADE_RELATIONS);
        $jueTai = $facts->hasPlatePattern('fanyin');

        $branchName = static fn (int $branch): string => PanCalculator::$dizhi[$branch] ?? '?';
        $transmissionNames = implode('、', array_map($branchName, $transmissions));

        return new RuleMatch(
            code: self::RULE_CODE,
            name: self::NAME,
            group: self::GROUP,
            description: self::DESCRIPTION,
            gua: self::GUA,
            guaSymbol: self::GUA_SYMBOL,
            xiang: self::XIANG,
            evidence: [
                'transmissions' => $transmissions,
                'foundations' => [
                    [
                        'code' => 'three_transmissions_all_meng',
                        'title' => '三传俱孟',
                        'description' => '初传、中传、末传全部属于寅、巳、申、亥四孟。',
                        'matched' => true,
                        'evidence' => "三传为{$transmissionNames}，全部属于四孟。",
                    ],
                ],
                'judgments' => [
                    [
                        'code' => 'bing_tai',
                        'effect' => 'reduce',
                        'label' => '病胎：进步长生',
                        'description' => '寅加巳、巳加申、申加亥、亥加寅；《订讹》主事速、怀胎有忧。',
                        'matched' => $bingTai,
                        'evidence' => $bingTai ? '当前天地盘为进步长生，命中病胎。' : '当前天地盘不是进步长生。',
                    ],
                    [
                        'code' => 'sheng_tai',
                        'effect' => 'increase',
                        'label' => '生胎：退步长生',
                        'description' => '寅加亥、亥加申、申加巳、巳加寅；《订讹》主事迟、怀胎大吉。',
                        'matched' => $shengTai,
                        'evidence' => $shengTai ? '当前天地盘为退步长生，命中生胎。' : '当前天地盘不是退步长生。',
                    ],
                    [
                        'code' => 'jue_tai',
                        'effect' => 'reduce',
                        'label' => '绝胎：反吟四孟',
                        'description' => '玄胎又见反吟，传统称绝胎。',
                        'matched' => $jueTai,
                        'evidence' => $jueTai ? '当前盘同时为反吟，命中绝胎。' : '当前盘不是反吟。',
                    ],
                ],
                'uncovered' => self::UNCOVERED,
            ],
        );
    }

    /** @param array<int, int> $relations */
    private static function matchesPlateRelations(mixed $tianpan, array $relations): bool
    {
        if (! is_array($tianpan) || count($tianpan) < 12) {
            return false;
        }

        foreach ($relations as $ground => $heaven) {
            if (($tianpan[$ground] ?? null) !== $heaven) {
                return false;
            }
        }

        return true;
    }
}
