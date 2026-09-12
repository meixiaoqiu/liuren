<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/** 文件作用：按冻结口径判断刑伤课；方向固定为 xingOf(initial) == target。 */
final class XingshangRule implements PanRule
{
    public function code(): string
    {
        return 'lesson.xingshang';
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $dayStem = $facts->get('rigan');
        $dayBranch = $facts->get('rizhi');
        $initial = $facts->get('sanchuan0');
        if (! is_int($dayStem) || ! is_int($dayBranch) || ! is_int($initial)) {
            return null;
        }

        $stemLodge = $facts->stemLodgingBranch($dayStem);
        $punished = PanCalculator::$xing[$initial] ?? null;
        if (! is_int($stemLodge) || ! is_int($punished)) {
            return null;
        }

        $querent = $facts->personByRole('querent');
        $nianming = is_array($querent) ? ($querent['nianming'] ?? null) : null;
        $xingnian = is_array($querent) ? ($querent['xingnian'] ?? null) : null;
        $routes = [];
        $addRoute = static function (string $route, int $target, array $extra = []) use (&$routes, $initial, $punished): void {
            if ($punished === $target) {
                $routes[] = ['route' => $route, 'initial' => $initial, 'punished' => $punished, 'target' => $target, ...$extra];
            }
        };

        $addRoute('stem', $stemLodge, ['day_stem' => $dayStem, 'stem_lodge' => $stemLodge]);
        $addRoute('branch', $dayBranch);
        if (is_int($nianming)) {
            $addRoute('nianming', $nianming, ['role' => 'querent', 'nianming' => $nianming]);
        }
        if (is_int($xingnian)) {
            $addRoute('xingnian', $xingnian, ['role' => 'querent', 'xingnian' => $xingnian]);
        }
        if ($routes === []) {
            return null;
        }

        $labels = ['stem' => '刑干', 'branch' => '刑支', 'nianming' => '刑本命', 'xingnian' => '刑行年'];
        $foundations = array_map(static function (array $route) use ($labels): array {
            $initial = PanCalculator::$dizhi[$route['initial']] ?? '?';
            $target = PanCalculator::$dizhi[$route['target']] ?? '?';
            $targetRole = match ($route['route']) {
                'stem' => '日干寄宫', 'branch' => '日支', 'nianming' => '占者本命', 'xingnian' => '占者行年',
            };

            return ['title' => $labels[$route['route']], 'detail' => "初传{$initial}刑{$target}，{$target}为{$targetRole}，命中{$labels[$route['route']]}。"];
        }, $routes);

        $uncovered = [
            '递互相刑、乘凶将、遇日鬼及刑德格不作为刑伤课主体入口',
            '只考察初传之刑，中传、末传及四课任意相刑不参与判断',
        ];
        if (! is_int($nianming) && ! is_int($xingnian)) {
            $uncovered[] = '当前没有占者本命、行年资料，人物两路未参与判断。';
        }

        return new RuleMatch(
            code: $this->code(), name: '刑伤课', group: '六十四课',
            description: '初传之刑落日干寄宫、日支、占者本命或行年之一。',
            gua: '讼', guaSymbol: '䷅',
            xiang: '偏欹失位，家门不昌。胎孕欲堕，婚姻不良。征下顺利，斗上刑伤。谋为乖戾，凡事遭殃。',
            evidence: [
                'initial' => $initial, 'punished_branch' => $punished,
                'matched_routes' => array_column($routes, 'route'), 'routes' => $routes,
                'foundations' => $foundations,
                'judgments' => [['label' => '刑伤课成立', 'evidence' => implode('；', array_column($foundations, 'detail'))]],
                'uncovered' => $uncovered,
            ],
        );
    }
}
