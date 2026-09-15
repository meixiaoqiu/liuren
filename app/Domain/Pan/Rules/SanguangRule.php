<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/** 文件作用：判断日、辰、用神旺相且三处乘吉将所成的三光课。 */
final class SanguangRule implements PanRule
{
    use LessonDefinitionDefaults;

    /** @var list<int> */
    protected const AUSPICIOUS_GENERALS = [0, 3, 5, 8, 10, 11];

    protected const RULE_CODE = 'lesson.sanguang';

    protected const NAME = '三光课';

    protected const GROUP = '六十四课';

    protected const DESCRIPTION = '日干、日支与发用均得季节旺相，日上神、辰上神与发用又均乘吉将。';

    protected const GUA = '贲';

    protected const GUA_SYMBOL = '䷕';

    protected const XIANG = '课入三光，万事吉昌。刑囚释放，疾病安康。市贾得利，谋干俱良。福佑自至，凶祸消亡。';

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
                    'code' => 'day_stem_wang_xiang',
                    'title' => '日干得旺相',
                    'description' => '日干按本日所在月令得旺或相。',
                ],
                [
                    'code' => 'day_branch_wang_xiang',
                    'title' => '日支得旺相',
                    'description' => '日支按本日所在月令得旺或相。',
                ],
                [
                    'code' => 'initial_wang_xiang',
                    'title' => '初传得旺相',
                    'description' => '初传（发用）按本日所在月令得旺或相。',
                ],
                [
                    'code' => 'day_upper_auspicious_general',
                    'title' => '日上神乘吉将',
                    'description' => '日干寄宫上神乘贵人、六合、青龙、太常、太阴、天后六吉将之一。',
                ],
                [
                    'code' => 'branch_upper_auspicious_general',
                    'title' => '辰上神乘吉将',
                    'description' => '日支上神乘贵人、六合、青龙、太常、太阴、天后六吉将之一。',
                ],
                [
                    'code' => 'initial_auspicious_general',
                    'title' => '初传乘吉将',
                    'description' => '初传所乘天将属于贵人、六合、青龙、太常、太阴、天后六吉将之一。',
                ],
            ],
            'judgments' => [],
        ];
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $stem = $facts->get('rigan');
        $branch = $facts->get('rizhi');
        $initial = $facts->get('sanchuan0');
        $lessons = $facts->get('sike');

        if (! is_int($stem) || ! is_int($branch) || ! is_int($initial) || ! is_array($lessons)) {
            return null;
        }

        $dayUpper = $lessons[1] ?? null;
        $branchUpper = $lessons[5] ?? null;

        if (! is_int($dayUpper) || ! is_int($branchUpper)) {
            return null;
        }

        $generals = [
            'day_upper' => $facts->generalRidingBranch($dayUpper),
            'branch_upper' => $facts->generalRidingBranch($branchUpper),
            'initial' => $facts->generalRidingBranch($initial),
        ];

        $dayStemWang = $facts->isStemWangOrXiang($stem);
        $dayBranchWang = $facts->isBranchWangOrXiang($branch);
        $initialWang = $facts->isBranchWangOrXiang($initial);
        $dayUpperAuspicious = in_array($generals['day_upper'], self::AUSPICIOUS_GENERALS, true);
        $branchUpperAuspicious = in_array($generals['branch_upper'], self::AUSPICIOUS_GENERALS, true);
        $initialAuspicious = in_array($generals['initial'], self::AUSPICIOUS_GENERALS, true);

        if (! $dayStemWang || ! $dayBranchWang || ! $initialWang
            || ! $dayUpperAuspicious || ! $branchUpperAuspicious || ! $initialAuspicious) {
            return null;
        }

        $stemName = PanCalculator::$tiangan[$stem] ?? '?';
        $branchName = PanCalculator::$dizhi[$branch] ?? '?';
        $initialName = PanCalculator::$dizhi[$initial] ?? '?';
        $dayUpperName = PanCalculator::$dizhi[$dayUpper] ?? '?';
        $branchUpperName = PanCalculator::$dizhi[$branchUpper] ?? '?';
        $generalNames = [
            'day_upper' => PanCalculator::$tianjiang[$generals['day_upper']] ?? '?',
            'branch_upper' => PanCalculator::$tianjiang[$generals['branch_upper']] ?? '?',
            'initial' => PanCalculator::$tianjiang[$generals['initial']] ?? '?',
        ];

        $foundations = [
            [
                'code' => 'day_stem_wang_xiang',
                'title' => '日干得旺相',
                'description' => '日干按本日所在月令得旺或相。',
                'matched' => true,
                'evidence' => '日干'.$stemName.'得季节'.$facts->stemSeasonalStrength($stem).'。',
            ],
            [
                'code' => 'day_branch_wang_xiang',
                'title' => '日支得旺相',
                'description' => '日支按本日所在月令得旺或相。',
                'matched' => true,
                'evidence' => '日支'.$branchName.'得季节'.$facts->branchSeasonalStrength($branch).'。',
            ],
            [
                'code' => 'initial_wang_xiang',
                'title' => '初传得旺相',
                'description' => '初传（发用）按本日所在月令得旺或相。',
                'matched' => true,
                'evidence' => '初传'.$initialName.'得季节'.$facts->branchSeasonalStrength($initial).'。',
            ],
            [
                'code' => 'day_upper_auspicious_general',
                'title' => '日上神乘吉将',
                'description' => '日干寄宫上神乘贵人、六合、青龙、太常、太阴、天后六吉将之一。',
                'matched' => true,
                'evidence' => '日上神'.$dayUpperName.'乘'.$generalNames['day_upper'].'。',
            ],
            [
                'code' => 'branch_upper_auspicious_general',
                'title' => '辰上神乘吉将',
                'description' => '日支上神乘贵人、六合、青龙、太常、太阴、天后六吉将之一。',
                'matched' => true,
                'evidence' => '辰上神'.$branchUpperName.'乘'.$generalNames['branch_upper'].'。',
            ],
            [
                'code' => 'initial_auspicious_general',
                'title' => '初传乘吉将',
                'description' => '初传所乘天将属于贵人、六合、青龙、太常、太阴、天后六吉将之一。',
                'matched' => true,
                'evidence' => '初传'.$initialName.'乘'.$generalNames['initial'].'。',
            ],
        ];

        return new RuleMatch(
            code: $this->code(),
            name: self::NAME,
            group: self::GROUP,
            description: self::DESCRIPTION,
            gua: self::GUA,
            guaSymbol: self::GUA_SYMBOL,
            xiang: self::XIANG,
            evidence: [
                'foundations' => $foundations,
                'judgments' => [],
                'month_branch' => $facts->get('yuezhi'),
                'day_stem' => $stem,
                'day_branch' => $branch,
                'initial_transmission' => $initial,
                'generals' => $generals,
                'reasoning' => [
                    'wang_xiang_elements' => $facts->wangXiangElements(),
                    'seasonal_period' => $facts->seasonalPeriod(),
                    'positions' => [
                        [
                            'label' => '日',
                            'subject_type' => 'stem',
                            'subject' => $stem,
                            'element' => $facts->stemElement($stem),
                            'strength' => $facts->stemSeasonalStrength($stem),
                            'upper_branch' => $dayUpper,
                            'general' => $generals['day_upper'],
                        ],
                        [
                            'label' => '辰',
                            'subject_type' => 'branch',
                            'subject' => $branch,
                            'element' => $facts->branchElement($branch),
                            'strength' => $facts->branchSeasonalStrength($branch),
                            'upper_branch' => $branchUpper,
                            'general' => $generals['branch_upper'],
                        ],
                        [
                            'label' => '用',
                            'subject_type' => 'branch',
                            'subject' => $initial,
                            'element' => $facts->branchElement($initial),
                            'strength' => $facts->branchSeasonalStrength($initial),
                            'upper_branch' => $initial,
                            'general' => $generals['initial'],
                        ],
                    ],
                ],
            ],
        );
    }
}
