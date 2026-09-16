<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/**
 * 文件作用：按《六壬大全》正文冻结口径判断第57课盘珠课。
 *
 * 规则边界：四建（太岁、月建、日支、占时）与三传必须全部出现在四课整体结构的地支集合中；
 * 日用旺相、神将吉凶、四课不备、反吟、斩关、重阴重阳等只属后续吉凶判断，不限制成课。
 */
final class PanzhuRule implements PanRule
{
    use LessonDefinitionDefaults;

    public const RULE_CODE = 'lesson.panzhu';

    public const NAME = '盘珠课';

    public const GROUP = '六十四课';

    public const GUA = '大壮';

    public const GUA_SYMBOL = '䷡';

    public const DESCRIPTION = '太岁、月建、日支、占时及三传全部在四课整体结构的地支集合中，为盘珠课。';

    public const XIANG = '三传四课，偶合异常。吉则成福，凶则成殃。贼不出境，行人还乡。阴私解释，事反不良。';

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
                    'code' => 'four_establishments_in_lessons',
                    'title' => '天心部分：四建尽在四课',
                    'description' => '太岁、月建、日支、占时四建必须全部出现在四课整体结构的地支集合中；这是天心格的正文路线。',
                ],
                [
                    'code' => 'transmissions_in_lessons',
                    'title' => '回还部分：三传尽在四课',
                    'description' => '初传、中传、末传必须全部出现在同一四课整体结构的地支集合中；这是回还格。',
                ],
                [
                    'code' => 'two_grids_combined',
                    'title' => '二格合一',
                    'description' => '前两项必须同时成立，符合正文「此二格合一，如盘中走珠，不出于外，故名盘珠」。',
                ],
            ],
            'judgments' => [
                [
                    'code' => 'wangxiang_good_generals',
                    'effect' => 'increase',
                    'label' => '日用旺相、神将吉',
                    'description' => '《六壬大全》称「如日用旺相，神将吉，大利」。',
                ],
                [
                    'code' => 'incomplete_four_lessons',
                    'effect' => 'increase',
                    'label' => '四课不备，守旧动作亦吉',
                    'description' => '四课不备不否决盘珠；正文明确称「或四课不备，守旧动作亦吉」。',
                ],
                [
                    'code' => 'fanyin_distance_shift',
                    'effect' => 'neutral',
                    'label' => '反吟：移远就近、缓事为速',
                    'description' => '反吟课本主远；若初传太岁、中末月日，正文断为移远就近、缓事为速。',
                ],
                [
                    'code' => 'zhanguan_empty_later',
                    'effect' => 'neutral',
                    'label' => '斩关、中末空：动中不动',
                    'description' => '斩关课日辰乘龙合、占时为用且中末传空，正文断为动中不动、寻远就近。',
                ],
                [
                    'code' => 'soft_day_maoxing_hidden',
                    'effect' => 'neutral',
                    'label' => '柔日昴星：伏匿不动',
                    'description' => '柔日昴星则伏匿不动，属于盘珠课的动静修证。',
                ],
                [
                    'code' => 'heavy_yin',
                    'effect' => 'reduce',
                    'label' => '重阴忧女',
                    'description' => '太岁加河魁、河魁加太岁为重阴，正文断为忧女。',
                ],
                [
                    'code' => 'heavy_yang',
                    'effect' => 'reduce',
                    'label' => '重阳忧男',
                    'description' => '月建加天罡、天罡加月为重阳，正文断为忧男。',
                ],
                [
                    'code' => 'yin_over_yang',
                    'effect' => 'reduce',
                    'label' => '阴覆阳：事在内',
                    'description' => '正文称「戌与岁加月，为阴覆阳，事在内」。',
                ],
                [
                    'code' => 'yang_over_yin',
                    'effect' => 'reduce',
                    'label' => '阳覆阴：事在外',
                    'description' => '正文称「月与辰加岁，为阳覆阴，事在外」。',
                ],
                [
                    'code' => 'adverse_query_types',
                    'effect' => 'reduce',
                    'label' => '病讼、生产、忧疑、解释反凶',
                    'description' => '盘珠课占病讼、生产、忧疑、解释等事，正文明确断为反凶。',
                ],
                [
                    'code' => 'qiu_si_bad_generals',
                    'effect' => 'reduce',
                    'label' => '日用囚死、神将凶',
                    'description' => '日用囚死且神将凶，正文断为凡事成祸、忧疑难解、灾甚。',
                ],
            ],
        ];
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $analysis = PanzhuSupport::analyze($facts);
        if ($analysis === null) {
            return null;
        }

        $four = array_values($analysis['four_establishments']);
        $lessonBranches = $analysis['lesson_branches'];
        $transmissions = $analysis['transmissions'];
        $fourInLessons = PanzhuSupport::allIn($four, $lessonBranches);
        $transmissionsInLessons = PanzhuSupport::allIn($transmissions, $lessonBranches);

        if (! ($fourInLessons && $transmissionsInLessons)) {
            return null;
        }

        $branch = static fn (int $value): string => PanCalculator::$dizhi[$value] ?? '?';
        $fourMeta = $analysis['four_establishments'];
        $fourDetail = '太岁'.$branch($fourMeta['year'])
            .'、月建'.$branch($fourMeta['month'])
            .'、日支'.$branch($fourMeta['day'])
            .'、占时'.$branch($fourMeta['hour']);
        $lessonDetail = PanzhuSupport::branchNames($lessonBranches);
        $transmissionDetail = PanzhuSupport::branchNames($transmissions);

        return new RuleMatch(
            code: self::RULE_CODE,
            name: self::NAME,
            group: self::GROUP,
            description: self::DESCRIPTION,
            gua: self::GUA,
            guaSymbol: self::GUA_SYMBOL,
            xiang: self::XIANG,
            evidence: [
                'lesson_branches' => $lessonBranches,
                'four_establishments' => $fourMeta,
                'transmissions' => $transmissions,
                'four_establishments_in_lessons' => true,
                'transmissions_in_lessons' => true,
                'foundations' => [
                    [
                        'code' => 'four_establishments_in_lessons',
                        'title' => '天心部分：四建尽在四课',
                        'description' => '太岁、月建、日支、占时四建必须全部出现在四课整体结构的地支集合中；这是天心格的正文路线。',
                        'matched' => true,
                        'evidence' => "{$fourDetail}全部见于四课地支集合（{$lessonDetail}）。",
                    ],
                    [
                        'code' => 'transmissions_in_lessons',
                        'title' => '回还部分：三传尽在四课',
                        'description' => '初传、中传、末传必须全部出现在同一四课整体结构的地支集合中；这是回还格。',
                        'matched' => true,
                        'evidence' => "三传{$transmissionDetail}全部见于四课地支集合（{$lessonDetail}）。",
                    ],
                    [
                        'code' => 'two_grids_combined',
                        'title' => '二格合一',
                        'description' => '前两项必须同时成立，符合正文「此二格合一，如盘中走珠，不出于外，故名盘珠」。',
                        'matched' => true,
                        'evidence' => '四建与三传均不出四课范围，盘珠课成立。',
                    ],
                ],
                'judgments' => [],
                'uncovered' => [
                    '日用旺相、神将吉凶与四课不备等课义已在详情页静态列明，但当前盘动态触发尚未程序化',
                    '反吟、斩关、中末空、柔日昴星等远近动静修证已在详情页静态列明，但当前盘动态触发尚未程序化',
                    '重阴、重阳、阴覆阳、阳覆阴及年命参与的吉凶修证已在详情页静态列明，但当前盘动态触发尚未程序化',
                    '病讼、生产、忧疑、解释等占类反断已在详情页静态列明，但因缺少占事上下文暂不做当前盘动态触发',
                ],
            ],
        );
    }
}
