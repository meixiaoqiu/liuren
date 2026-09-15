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
                        'title' => '天心部分：四建尽在四课',
                        'detail' => "{$fourDetail}全部见于四课地支集合（{$lessonDetail}）。",
                    ],
                    [
                        'title' => '回还部分：三传尽在四课',
                        'detail' => "三传{$transmissionDetail}全部见于四课地支集合（{$lessonDetail}）。",
                    ],
                    [
                        'title' => '二格合一',
                        'detail' => '四建与三传均不出四课范围，符合正文「此二格合一，如盘中走珠，不出于外，故名盘珠」。',
                    ],
                ],
                'judgments' => [],
                'uncovered' => [
                    '日用旺相与神将吉凶尚未作为盘珠课课内 judgment 程序化',
                    '反吟、斩关、中末空、柔日昴星等远近动静修证尚未程序化',
                    '重阴、重阳、阴覆阳、阳覆阴及年命参与的吉凶修证尚未程序化',
                    '病讼、生产、忧疑、解释等占类反断属于占事上下文，尚未程序化',
                ],
            ],
        );
    }
}
