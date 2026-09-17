<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/**
 * 文件作用：判断第60课连珠课，并结构化进退连珠、岁月日顺逆与三奇联珠。
 *
 * 冻结口径：
 * 1. 主体有两条独立入口：同一方孟仲季顺/逆相连，或三传依次等于太岁、月建、日支（或逆序）；
 * 2. 任意三个数值连续的地支并不当然成立，例如辰巳午跨越两方，不属于“孟仲季相连”入口；
 * 3. 岁月日入口不自创“三支必须互异”限制，太岁、月建、日支重复时仍按逐传对应判断；
 * 4. 夹定三传、夹定虚一、夹不住、朝日、朝支、间传、撞干、撞支均不纳入本课主体 matcher。
 */
final class LianzhuRule implements PanRule
{
    use LessonDefinitionDefaults;

    public const RULE_CODE = 'lesson.lianzhu';

    public const NAME = '连珠课';

    public const GROUP = '六十四课';

    public const GUA = '复';

    public const GUA_SYMBOL = '䷗';

    public const DESCRIPTION = '三传同一方孟仲季顺逆相连，或三传依次为太岁、月建、日支（或逆序），为连珠课。';

    public const XIANG = '阴阳拱夹，奇偶有主。凶则重重，吉当累累。孕必连胎，事获交逢。时旱多晴，天阴久雨。若传进宜进，贵顺事顺速成。值空亡则宜退，可以全身远害。传退宜退，贵逆迟阻。遇空亡则宜进，可以消灾避祸。或三传亥子丑，日月星奇全者，为三奇联珠。主万事吉和，乘吉将尤吉，当应复六五敦复无悔之象也。';

    /** @var list<list<int>> */
    private const MENG_FORWARD = [
        [2, 3, 4],
        [5, 6, 7],
        [8, 9, 10],
        [11, 0, 1],
    ];

    /** @var list<list<int>> */
    private const MENG_REVERSE = [
        [4, 3, 2],
        [7, 6, 5],
        [10, 9, 8],
        [1, 0, 11],
    ];

    private const UNCOVERED = [
        '进连珠逢空亡宜退、退连珠逢空亡宜进的动态判断，待统一旬空作用范围后再程序化',
        '贵人顺逆与传进退的快慢联动尚未程序化',
        '夹定三传、夹定虚一、夹不住、朝日、朝支应作为独立 structure 继续研究，不作为连珠主体入口',
        '间传课及其撞干、撞支等结构另属间传体系，不纳入连珠课',
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
                    'code' => 'lianzhu_route',
                    'title' => '连珠成课路线',
                    'description' => '满足任一路线即可：① 三传为同一方孟仲季顺传或逆传；② 三传依次等于太岁、月建、日支，或反向为日支、月建、太岁。',
                ],
            ],
            'judgments' => [
                [
                    'code' => 'progressive_lianzhu',
                    'effect' => 'increase',
                    'label' => '进连珠',
                    'description' => '孟仲季顺序相连；《订讹》断“进连珠事顺”，《灵觉经》又断顺序成一气则事无阻滞而速遂。',
                ],
                [
                    'code' => 'retrograde_lianzhu',
                    'effect' => 'reduce',
                    'label' => '退连珠（失友格）',
                    'description' => '孟仲季逆序相连；正文称退连茹又名失友格，《订讹》断“退连珠事逆”。',
                ],
                [
                    'code' => 'year_month_day_forward',
                    'effect' => 'increase',
                    'label' => '岁月日顺连珠',
                    'description' => '初传为太岁支、中传为月建支、末传为日支；《灵觉经》断主事速至。',
                ],
                [
                    'code' => 'day_month_year_reverse',
                    'effect' => 'reduce',
                    'label' => '日月岁逆连珠',
                    'description' => '初传为日支、中传为月建支、末传为太岁支；《灵觉经》断其至迟。',
                ],
                [
                    'code' => 'sanqi_lianzhu',
                    'effect' => 'increase',
                    'label' => '三奇联珠',
                    'description' => '三传亥、子、丑，正文以日月星奇全论，主万事吉和，乘吉将尤吉。',
                ],
            ],
        ];
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $transmissions = [];
        foreach (['sanchuan0', 'sanchuan1', 'sanchuan2'] as $key) {
            $branch = $facts->get($key);
            if (! is_int($branch) || $branch < 0 || $branch > 11) {
                return null;
            }
            $transmissions[] = $branch;
        }

        $year = $facts->get('nianzhi');
        $month = $facts->get('yuezhi');
        $day = $facts->get('rizhi');

        $mengForward = in_array($transmissions, self::MENG_FORWARD, true);
        $mengReverse = in_array($transmissions, self::MENG_REVERSE, true);

        $hasCalendarBranches = is_int($year) && $year >= 0 && $year <= 11
            && is_int($month) && $month >= 0 && $month <= 11
            && is_int($day) && $day >= 0 && $day <= 11;
        $yearMonthDayForward = $hasCalendarBranches && $transmissions === [$year, $month, $day];
        $dayMonthYearReverse = $hasCalendarBranches && $transmissions === [$day, $month, $year];

        if (! $mengForward && ! $mengReverse && ! $yearMonthDayForward && ! $dayMonthYearReverse) {
            return null;
        }

        $routeFlags = [
            'meng_forward' => $mengForward,
            'meng_reverse' => $mengReverse,
            'year_month_day_forward' => $yearMonthDayForward,
            'day_month_year_reverse' => $dayMonthYearReverse,
        ];
        $routeLabels = [
            'meng_forward' => '孟仲季进连珠',
            'meng_reverse' => '孟仲季退连珠',
            'year_month_day_forward' => '岁月日顺连珠',
            'day_month_year_reverse' => '日月岁逆连珠',
        ];
        $matchedRoutes = [];
        foreach ($routeFlags as $code => $matched) {
            if ($matched) {
                $matchedRoutes[] = $code;
            }
        }

        $branchName = static fn (int $branch): string => PanCalculator::$dizhi[$branch] ?? '?';
        $transmissionNames = implode('、', array_map($branchName, $transmissions));
        $matchedRouteNames = implode('、', array_map(
            static fn (string $code): string => $routeLabels[$code],
            $matchedRoutes,
        ));
        $sanqi = $transmissions === [11, 0, 1];

        $calendarEvidence = $hasCalendarBranches
            ? sprintf('太岁%s、月建%s、日支%s。', $branchName($year), $branchName($month), $branchName($day))
            : '岁、月、日支字段不完整；当前仅按孟仲季路线判断。';

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
                'year_branch' => $hasCalendarBranches ? $year : null,
                'month_branch' => $hasCalendarBranches ? $month : null,
                'day_branch' => $hasCalendarBranches ? $day : null,
                'route_flags' => $routeFlags,
                'matched_routes' => $matchedRoutes,
                'foundations' => [
                    [
                        'code' => 'lianzhu_route',
                        'title' => '连珠成课路线',
                        'description' => '孟仲季同方顺逆相连，或岁月日三支按顺逆次序进入三传，任一路线成立即可。',
                        'matched' => true,
                        'evidence' => "三传为{$transmissionNames}；命中{$matchedRouteNames}。{$calendarEvidence}",
                    ],
                ],
                'judgments' => [
                    [
                        'code' => 'progressive_lianzhu',
                        'effect' => 'increase',
                        'label' => '进连珠',
                        'description' => '孟仲季顺序相连，传统主事顺、较速。',
                        'matched' => $mengForward,
                        'evidence' => $mengForward ? "三传{$transmissionNames}为同一方孟仲季顺传。" : '当前三传不是孟仲季顺传。',
                    ],
                    [
                        'code' => 'retrograde_lianzhu',
                        'effect' => 'reduce',
                        'label' => '退连珠（失友格）',
                        'description' => '孟仲季逆序相连，传统主事逆；正文又名失友格。',
                        'matched' => $mengReverse,
                        'evidence' => $mengReverse ? "三传{$transmissionNames}为同一方孟仲季逆传。" : '当前三传不是孟仲季逆传。',
                    ],
                    [
                        'code' => 'year_month_day_forward',
                        'effect' => 'increase',
                        'label' => '岁月日顺连珠',
                        'description' => '初传太岁、中传月建、末传日支，传统主事速至。',
                        'matched' => $yearMonthDayForward,
                        'evidence' => $yearMonthDayForward ? "三传{$transmissionNames}逐位对应太岁、月建、日支。" : '当前三传不按太岁、月建、日支顺序排列。',
                    ],
                    [
                        'code' => 'day_month_year_reverse',
                        'effect' => 'reduce',
                        'label' => '日月岁逆连珠',
                        'description' => '初传日支、中传月建、末传太岁，传统主其至迟。',
                        'matched' => $dayMonthYearReverse,
                        'evidence' => $dayMonthYearReverse ? "三传{$transmissionNames}逐位对应日支、月建、太岁。" : '当前三传不按日支、月建、太岁逆序排列。',
                    ],
                    [
                        'code' => 'sanqi_lianzhu',
                        'effect' => 'increase',
                        'label' => '三奇联珠',
                        'description' => '三传亥子丑，正文以日月星奇全论，主万事吉和。',
                        'matched' => $sanqi,
                        'evidence' => $sanqi ? '三传为亥、子、丑，命中三奇联珠。' : '当前三传不是亥、子、丑。',
                    ],
                ],
                'uncovered' => self::UNCOVERED,
            ],
        );
    }
}
