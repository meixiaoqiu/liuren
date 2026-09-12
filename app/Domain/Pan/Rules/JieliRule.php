<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\BranchRelations;
use App\Domain\Pan\Facts\PanFacts;

/**
 * 文件作用：按《六壬大全》解离课当前项目暂定口径判断夫妻行年冲克与上下神交叉克贼。
 *
 * 当前冻结为项目解释而非宣称古籍无歧义：
 * 1. 夫妻行年相冲或相克；
 * 2. 夫行年与妻行年上神有克贼，或夫行年上神与妻行年有克贼。
 * 正文例另见双方行年上神相克，仅作增强信息展示，不作为成课必要条件。
 */
final class JieliRule implements PanRule
{
    /** @var list<string> */
    private const BRANCH_NAMES = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];

    /** @var list<string> */
    private const ELEMENT_NAMES = ['木', '火', '土', '金', '水'];

    public function code(): string
    {
        return 'lesson.jieli';
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $querent = $facts->personByRole('querent');
        $spouse = $facts->personByRole('spouse');
        $tianpan = $facts->get('tianpan');

        if ($querent === null || $spouse === null || ! is_array($tianpan)) {
            return null;
        }

        $husband = ($querent['gender'] ?? null) === 'male' ? $querent : $spouse;
        $wife = ($querent['gender'] ?? null) === 'female' ? $querent : $spouse;

        if (($husband['gender'] ?? null) !== 'male' || ($wife['gender'] ?? null) !== 'female') {
            return null;
        }

        $fuDown = $husband['xingnian'] ?? null;
        $qiDown = $wife['xingnian'] ?? null;
        if (! is_int($fuDown) || ! is_int($qiDown)
            || ! isset($tianpan[$fuDown], $tianpan[$qiDown])
            || ! is_int($tianpan[$fuDown]) || ! is_int($tianpan[$qiDown])) {
            return null;
        }

        $fuUpper = $tianpan[$fuDown];
        $qiUpper = $tianpan[$qiDown];

        $xingnianClash = BranchRelations::clashOf($fuDown) === $qiDown;
        $xingnianKe = $this->hasKe($facts, $fuDown, $qiDown);
        $fuLowerQiUpperKe = $this->hasKe($facts, $fuDown, $qiUpper);
        $fuUpperQiLowerKe = $this->hasKe($facts, $fuUpper, $qiDown);
        $upperUpperKe = $this->hasKe($facts, $fuUpper, $qiUpper);

        $condition1 = $xingnianClash || $xingnianKe;
        $condition2 = $fuLowerQiUpperKe || $fuUpperQiLowerKe;
        if (! $condition1 || ! $condition2) {
            return null;
        }

        $condition1Parts = [];
        if ($xingnianClash) {
            $condition1Parts[] = self::BRANCH_NAMES[min($fuDown, $qiDown)].self::BRANCH_NAMES[max($fuDown, $qiDown)].'六冲';
        }
        if ($xingnianKe) {
            $condition1Parts[] = $this->describeKe($facts, $fuDown, $qiDown);
        }

        $crossParts = [];
        if ($fuLowerQiUpperKe) {
            $crossParts[] = '夫下'.self::BRANCH_NAMES[$fuDown].'与妻上'.self::BRANCH_NAMES[$qiUpper].'：'.$this->describeKe($facts, $fuDown, $qiUpper);
        }
        if ($fuUpperQiLowerKe) {
            $crossParts[] = '夫上'.self::BRANCH_NAMES[$fuUpper].'与妻下'.self::BRANCH_NAMES[$qiDown].'：'.$this->describeKe($facts, $fuUpper, $qiDown);
        }

        $judgments = [];
        if ($upperUpperKe) {
            $judgments[] = [
                'effect' => 'increase',
                'label' => '双方行年上神另见克贼',
                'evidence' => '夫上'.self::BRANCH_NAMES[$fuUpper].'、妻上'.self::BRANCH_NAMES[$qiUpper].'另有'.$this->describeKe($facts, $fuUpper, $qiUpper).'；项目当前仅作正文例增强关系展示，不参与解离成课条件。',
            ];
        }

        return new RuleMatch(
            code: $this->code(),
            name: '解离课',
            group: '六十四课',
            description: '夫妻行年相冲或相克，且夫下与妻上、夫上与妻下两条交叉关系中至少一处有克贼。',
            evidence: [
                'fu_xingnian' => $fuDown,
                'qi_xingnian' => $qiDown,
                'fu_upper' => $fuUpper,
                'qi_upper' => $qiUpper,
                'xingnian_clash' => $xingnianClash,
                'xingnian_ke' => $xingnianKe,
                'fu_lower_qi_upper_ke' => $fuLowerQiUpperKe,
                'fu_upper_qi_lower_ke' => $fuUpperQiLowerKe,
                'upper_upper_ke' => $upperUpperKe,
                'condition1' => $condition1,
                'condition2' => $condition2,
                'foundations' => [
                    [
                        'title' => '夫妻行年冲克',
                        'detail' => '夫行年'.self::BRANCH_NAMES[$fuDown].'、妻行年'.self::BRANCH_NAMES[$qiDown].'；'.implode('，', $condition1Parts).'，第一条件成立。',
                    ],
                    [
                        'title' => '上下神交叉克贼',
                        'detail' => implode('；', $crossParts).'。两条十字任一成立，第二条件成立。',
                    ],
                ],
                'judgments' => $judgments,
                'uncovered' => [
                    '“上下神互相克贼”当前暂按夫下↔妻上或夫上↔妻下实现；正文只有一个主体标准例，未来可重新裁决',
                    '正文“午上寅怕申克”所见上神↔上神相克当前仅作增强关系，不作为成课必要条件',
                    '“冲克”当前按相冲 OR 相克解释；若后续发现必须兼具的直接证据，再收紧',
                    '课目“日辰互克上”暂未程序化，不借《毕法》补写主体 Boolean',
                    '课目“年命互克”中的本命 nianming 是否应参与尚未裁决，本轮只落实夫妻行年',
                    '解离课未见固定卦属直接依据，gua 与 guaSymbol 均留空',
                ],
            ],
        );
    }

    private function hasKe(PanFacts $facts, int $a, int $b): bool
    {
        $aElement = $facts->branchElement($a);
        $bElement = $facts->branchElement($b);

        return $this->restrains($aElement, $bElement) || $this->restrains($bElement, $aElement);
    }

    private function describeKe(PanFacts $facts, int $a, int $b): string
    {
        $aElement = $facts->branchElement($a);
        $bElement = $facts->branchElement($b);

        if ($this->restrains($aElement, $bElement)) {
            return $this->branchElementName($a, $aElement).'克'.$this->branchElementName($b, $bElement);
        }
        if ($this->restrains($bElement, $aElement)) {
            return $this->branchElementName($b, $bElement).'克'.$this->branchElementName($a, $aElement);
        }

        return '无克贼';
    }

    private function restrains(?int $source, ?int $target): bool
    {
        return $source !== null && $target !== null && ($source + 2) % 5 === $target;
    }

    private function branchElementName(int $branch, ?int $element): string
    {
        return (self::BRANCH_NAMES[$branch] ?? '?').($element === null ? '?' : (self::ELEMENT_NAMES[$element] ?? '?'));
    }
}
