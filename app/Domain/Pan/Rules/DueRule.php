<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/** 文件作用：按原始四课逐位统计度厄课；严格限定恰有三课克贼，四课全克不在本课。 */
final class DueRule implements PanRule
{
    public function code(): string
    {
        return 'lesson.due';
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

        $raw = [
            $this->relation($facts, '干阳', $stem, $sike[1], $shengke[0], true),
            $this->relation($facts, '干阴', $sike[2], $sike[3], $shengke[1]),
            $this->relation($facts, '支阳', $branch, $sike[5], $shengke[2]),
            $this->relation($facts, '支阴', $sike[6], $sike[7], $shengke[3]),
        ];
        $upCount = count(array_filter($shengke, fn (int $value): bool => $value === 1));
        $downCount = count(array_filter($shengke, fn (int $value): bool => $value === -1));
        $isYouDue = $upCount === 3;
        $isChangDue = $downCount === 3;
        if (! $isYouDue && ! $isChangDue) {
            return null;
        }

        $subtype = $isYouDue ? '幼度厄' : '长度厄';
        $selected = array_values(array_filter(
            $raw,
            fn (array $relation): bool => $relation['shengke'] === ($isYouDue ? 1 : -1),
        ));
        $direction = $isYouDue ? '上克下' : '下贼上';
        $details = implode('、', array_column($selected, 'restraint_fact'));

        return new RuleMatch(
            code: $this->code(), name: '度厄课', group: '六十四课',
            description: '四个原始课位中恰有三课上克下为幼度厄，恰有三课下贼上为长度厄。',
            gua: '剥', guaSymbol: '䷖',
            evidence: [
                'raw_lesson_relations' => $raw,
                'up_restrain_count' => $upCount,
                'down_restrain_count' => $downCount,
                'is_you_due' => $isYouDue,
                'is_chang_due' => $isChangDue,
                'subtype' => $subtype,
                'foundations' => [[
                    'title' => $subtype,
                    'detail' => "四个原始课位中3课{$direction}：{$details}。",
                ]],
                'judgments' => [[
                    'label' => $subtype,
                    'evidence' => "逐位统计原始四课，{$direction}数量恰为3，判为{$subtype}。",
                ]],
                'uncovered' => [
                    '《观月经》《袖中金》等文献所载异名不进入正式代码命名',
                    '四上俱克下、四下俱贼上属于下一课“无禄绝嗣课”的范围',
                ],
            ],
        );
    }

    private function relation(PanFacts $facts, string $position, int $lower, int $upper, int $shengke, bool $lowerIsStem = false): array
    {
        $lowerElement = $lowerIsStem ? $facts->stemElement($lower) : $facts->branchElement($lower);
        $upperElement = $facts->branchElement($upper);
        $lowerDisplay = ($lowerIsStem ? (PanCalculator::$tiangan[$lower] ?? '?') : (PanCalculator::$dizhi[$lower] ?? '?')).$this->elementName($lowerElement);
        $upperDisplay = (PanCalculator::$dizhi[$upper] ?? '?').$this->elementName($upperElement);
        $relation = match ($shengke) {
            1 => '上克下',
            2 => '上生下',
            -1 => '下贼上',
            -2 => '下生上',
            default => '比和',
        };
        $restraintFact = match ($shengke) {
            1 => "{$lowerDisplay}受{$upperDisplay}克",
            -1 => "{$upperDisplay}受{$lowerDisplay}克",
            default => null,
        };

        return [
            'position' => $position,
            'lower' => $lower,
            'lower_type' => $lowerIsStem ? 'stem' : 'branch',
            'lower_display' => $lowerDisplay,
            'upper' => $upper,
            'upper_type' => 'branch',
            'upper_display' => $upperDisplay,
            'shengke' => $shengke,
            'relation' => $relation,
            'display' => "{$lowerDisplay}{$relation}{$upperDisplay}",
            'restraint_fact' => $restraintFact,
        ];
    }

    private function elementName(?int $element): string
    {
        return $element === null ? '?' : (PanCalculator::$wuxing[$element] ?? '?');
    }
}
