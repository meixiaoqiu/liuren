<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/** 文件作用：独立判断盘珠课篇所附天心格；不以盘珠课或回还格成立为前提。 */
final class TianxinRule implements PanRule
{
    use LessonDefinitionDefaults;

    public function code(): string
    {
        return 'structure.tianxin';
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $analysis = PanzhuSupport::analyze($facts);
        if ($analysis === null) {
            return null;
        }

        $four = array_values($analysis['four_establishments']);
        $inLessons = PanzhuSupport::allIn($four, $analysis['lesson_branches']);
        $inTransmissions = PanzhuSupport::allIn($four, $analysis['transmissions']);
        if (! ($inLessons || $inTransmissions)) {
            return null;
        }

        $branch = static fn (int $value): string => PanCalculator::$dizhi[$value] ?? '?';
        $fourMeta = $analysis['four_establishments'];
        $fourDetail = '太岁'.$branch($fourMeta['year'])
            .'、月建'.$branch($fourMeta['month'])
            .'、日支'.$branch($fourMeta['day'])
            .'、占时'.$branch($fourMeta['hour']);
        $routes = [];
        if ($inLessons) {
            $routes[] = '四建俱在四课（'.PanzhuSupport::branchNames($analysis['lesson_branches']).'）';
        }
        if ($inTransmissions) {
            $routes[] = '四建俱在三传（'.PanzhuSupport::branchNames($analysis['transmissions']).'）';
        }

        return new RuleMatch(
            code: $this->code(),
            name: '天心格',
            group: '盘珠课体',
            description: '岁、月、日、时四建俱在四课，或依《订讹》俱在三传。',
            marker: '格',
            evidence: [
                'four_establishments' => $fourMeta,
                'lesson_branches' => $analysis['lesson_branches'],
                'transmissions' => $analysis['transmissions'],
                'four_establishments_in_lessons' => $inLessons,
                'four_establishments_in_transmissions' => $inTransmissions,
                'matched_routes' => array_values(array_filter([
                    $inLessons ? 'four_lessons' : null,
                    $inTransmissions ? 'transmissions' : null,
                ])),
                'detail' => $fourDetail.'；'.implode('；', $routes).'，成立天心格。',
            ],
        );
    }
}
