<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\BranchRelations;
use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/**
 * 文件作用：按冻结口径判断侵害课。
 * 仅检查日干寄宫或日支与各自直接上神作六害，且初传为该组上下二神之一。
 */
final class QinhaiRule implements PanRule
{
    public function code(): string
    {
        return 'lesson.qinhai';
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $stem = $facts->get('rigan');
        $branch = $facts->get('rizhi');
        $initial = $facts->get('sanchuan0');
        $tianpan = $facts->get('tianpan');
        if (! is_int($stem) || ! is_int($branch) || ! is_int($initial) || ! is_array($tianpan)) {
            return null;
        }

        $stemLower = $facts->stemLodgingBranch($stem);
        if (! is_int($stemLower) || ! isset($tianpan[$stemLower], $tianpan[$branch])
            || ! is_int($tianpan[$stemLower]) || ! is_int($tianpan[$branch])) {
            return null;
        }

        $routes = [];
        foreach ([
            ['route' => 'stem', 'lower' => $stemLower, 'upper' => $tianpan[$stemLower]],
            ['route' => 'branch', 'lower' => $branch, 'upper' => $tianpan[$branch]],
        ] as $candidate) {
            $pair = BranchRelations::haiPair($candidate['lower'], $candidate['upper']);
            if ($pair === null || ! in_array($initial, [$candidate['lower'], $candidate['upper']], true)) {
                continue;
            }

            $routes[] = [
                ...$candidate,
                'initial' => $initial,
                'hai_pair' => $pair,
                'initial_role' => $initial === $candidate['upper'] ? 'upper' : 'lower',
            ];
        }

        if ($routes === []) {
            return null;
        }

        $routeNames = ['stem' => '干路', 'branch' => '支路'];
        $foundations = array_map(function (array $route) use ($routeNames): array {
            $lower = PanCalculator::$dizhi[$route['lower']] ?? '?';
            $upper = PanCalculator::$dizhi[$route['upper']] ?? '?';
            $initial = PanCalculator::$dizhi[$route['initial']] ?? '?';
            $role = $route['initial_role'] === 'upper' ? '上神' : '下神';

            return [
                'title' => $routeNames[$route['route']],
                'detail' => "{$lower}上见{$upper}，{$lower}{$upper}六害；初传{$initial}为该组{$role}。",
            ];
        }, $routes);

        return new RuleMatch(
            code: $this->code(), name: '侵害课', group: '六十四课',
            description: '日干寄宫或日支与各自直接上神作六害，且初传为该组上下二神之一。',
            gua: '损', guaSymbol: '䷨',
            evidence: [
                'matched_routes' => array_column($routes, 'route'),
                'routes' => $routes,
                'initial' => $initial,
                'foundations' => $foundations,
                'judgments' => [[
                    'label' => '侵害课成立',
                    'evidence' => implode('；', array_column($foundations, 'detail')),
                ]],
                'uncovered' => [
                    '年命发用、初传临行年只作课义增强，不参与主体判断',
                    '交车六害、四课或三传中任意六害不属于本课入口',
                ],
            ],
        );
    }
}
