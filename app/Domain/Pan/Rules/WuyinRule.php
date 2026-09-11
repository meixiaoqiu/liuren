<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/** 按《六壬大全》严格口径判断：三课不备而原始四课有克，或日干、日支之上神交互克对方干支。 */
final class WuyinRule implements PanRule
{
    public function code(): string
    {
        return 'lesson.wuyin';
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $stem = $facts->get('rigan');
        $branch = $facts->get('rizhi');
        $sike = $facts->get('sike');
        if (! is_int($stem) || ! is_int($branch) || ! is_array($sike) || count($sike) < 8) {
            return null;
        }

        foreach ([1, 2, 3, 5, 6, 7] as $index) {
            if (! isset($sike[$index]) || ! is_int($sike[$index])) {
                return null;
            }
        }
        $shengke = [];
        for ($index = 0; $index < 4; $index++) {
            $value = $facts->get('wuxingShengke'.$index);
            if (! is_array($value) || ! isset($value[0]) || ! is_int($value[0])) {
                return null;
            }
            $shengke[] = $value[0];
        }

        $stemGround = $facts->stemLodgingBranch($stem);
        if ($stemGround === null) {
            return null;
        }

        $lessons = [
            ['position' => '干阳', 'polarity' => '阳', 'lower' => $stemGround, 'upper' => $sike[1]],
            ['position' => '干阴', 'polarity' => '阴', 'lower' => $sike[2], 'upper' => $sike[3]],
            ['position' => '支阳', 'polarity' => '阳', 'lower' => $branch, 'upper' => $sike[5]],
            ['position' => '支阴', 'polarity' => '阴', 'lower' => $sike[6], 'upper' => $sike[7]],
        ];

        $order = $stem % 2 === 0 ? [0, 1, 2, 3] : [2, 3, 0, 1];
        $unique = [];
        $seen = [];
        foreach ($order as $index) {
            $key = $lessons[$index]['lower'].':'.$lessons[$index]['upper'];
            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $lessons[$index];
            }
        }

        $isBubei = count($unique) === 3;
        $bubeiType = null;
        if ($isBubei) {
            $yangCount = count(array_filter($unique, fn (array $lesson): bool => $lesson['polarity'] === '阳'));
            $bubeiType = $yangCount === 2 ? '阴不备' : ($yangCount === 1 ? '阳不备' : null);
        }

        $raw = [
            $this->relation($facts, '干阳', $stem, $sike[1], $shengke[0], $facts->stemElement($stem), $facts->branchElement($sike[1]), true),
            $this->relation($facts, '干阴', $sike[2], $sike[3], $shengke[1]),
            $this->relation($facts, '支阳', $branch, $sike[5], $shengke[2]),
            $this->relation($facts, '支阴', $sike[6], $sike[7], $shengke[3]),
        ];
        $hasKe = collect($raw)->contains(fn (array $relation): bool => $relation['has_ke']);

        $stemUpperCross = $this->restrains($facts->branchElement($sike[1]), $facts->branchElement($branch));
        $branchUpperCross = $this->restrains($facts->branchElement($sike[5]), $facts->stemElement($stem));
        $bubeiPath = $isBubei && $hasKe;
        $crossPath = $stemUpperCross && $branchUpperCross;
        if (! $bubeiPath && ! $crossPath) {
            return null;
        }

        $paths = [];
        $foundations = [];
        if ($bubeiPath) {
            $paths[] = '三课不备且有克';
            $ke = collect($raw)->firstWhere('has_ke', true);
            $foundations[] = [
                'title' => '三课不备且有克',
                'detail' => "四个规范课结构中有一组重复，去重后仅三课；原始四课中{$ke['position']}课{$ke['display']}，故“不备有克”成立。",
            ];
        }
        if ($crossPath) {
            $paths[] = '日辰上神交互相克';
            $foundations[] = [
                'title' => '日辰上神交互相克',
                'detail' => '干上'.$this->branchName($sike[1]).$this->elementName($facts->branchElement($sike[1])).'克日支'.$this->branchName($branch).$this->elementName($facts->branchElement($branch)).'；支上'.$this->branchName($sike[5]).$this->elementName($facts->branchElement($sike[5])).'克日干'.(PanCalculator::$tiangan[$stem] ?? '?').$this->elementName($facts->stemElement($stem)).'；故日辰交互相克成立。',
            ];
        }

        return new RuleMatch(
            code: $this->code(), name: '芜淫课', group: '六十四课',
            description: '四课有克而缺一成三课，或干上神克日支、支上神克日干。',
            gua: '小畜', guaSymbol: '䷈',
            xiang: '阴阳不备，交克最嫌。利名碌碌，狱病淹淹。阴微晴久，阳少雨添。行人未至，征战愁眉。',
            evidence: [
                'day_stem' => $stem, 'day_branch' => $branch, 'stem_lodging_branch' => $stemGround,
                'canonical_lessons' => $lessons, 'unique_canonical_lessons' => $unique,
                'unique_lesson_count' => count($unique), 'is_bubei' => $isBubei, 'bubei_type' => $bubeiType,
                'raw_lesson_relations' => $raw, 'has_ke' => $hasKe,
                'stem_upper' => $sike[1], 'branch_upper' => $sike[5],
                'stem_upper_cross_restrains_branch' => $stemUpperCross,
                'branch_upper_cross_restrains_stem' => $branchUpperCross,
                'bubei_path' => $bubeiPath, 'cross_path' => $crossPath, 'paths' => $paths,
                'foundations' => $foundations,
                'judgments' => $bubeiType === null ? [] : [[
                    'label' => $bubeiType,
                    'evidence' => '三个独立课按'.($stem % 2 === 0 ? '刚日干阳起' : '柔日支阳起').'顺序认课，判为'.$bubeiType.'。',
                ]],
                'uncovered' => [
                    '“各自相生”及甲子例申子相生所引出的夫妻私情象义未作为主体条件程序化',
                    '阳不备利主、阴不备利客等兵占尚未动态程序化',
                    '三课不备又逢交克“占事最凶”以及凶将加重尚未程序化',
                    '四课备而神将吉、兼救神，以及不备无克“不凶”的吉凶修证尚未程序化',
                    '《观月经》“四课如不备，其卦号芜淫”以及“别责亦名芜淫”的宽口径仅分源保存，不放宽主体',
                ],
            ],
        );
    }

    private function relation(PanFacts $facts, string $position, int $lower, int $upper, int $shengke, ?int $lowerElement = null, ?int $upperElement = null, bool $lowerIsStem = false): array
    {
        $lowerElement ??= $facts->branchElement($lower);
        $upperElement ??= $facts->branchElement($upper);
        $relation = match ($shengke) {
            1 => '上克下',
            -1 => '下贼上',
            default => match (true) {
                $this->generates($upperElement, $lowerElement) => '上生下',
                $this->generates($lowerElement, $upperElement) => '下生上',
                default => '比和',
            },
        };

        $lowerName = $lowerIsStem ? (PanCalculator::$tiangan[$lower] ?? '?') : $this->branchName($lower);

        return ['position' => $position, 'lower' => $lower, 'upper' => $upper, 'relation' => $relation,
            'shengke' => $shengke, 'has_ke' => in_array($shengke, [1, -1], true),
            'display' => $lowerName.$this->elementName($lowerElement).$relation.$this->branchName($upper).$this->elementName($upperElement)];
    }

    private function restrains(?int $source, ?int $target): bool
    {
        return $source !== null && $target !== null && ($source + 2) % 5 === $target;
    }

    private function generates(?int $source, ?int $target): bool
    {
        return $source !== null && $target !== null && ($source + 1) % 5 === $target;
    }

    private function branchName(int $branch): string
    {
        return PanCalculator::$dizhi[$branch] ?? '?';
    }

    private function elementName(?int $element): string
    {
        return $element === null ? '?' : (PanCalculator::$wuxing[$element] ?? '?');
    }
}
