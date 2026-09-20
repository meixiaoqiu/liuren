<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/**
 * 文件作用：判断第64课物类课，并结构化初、中、末三传的五行、六亲、旺衰与所乘天将。
 *
 * 规则边界：凡正常盘存在合法初传即成课；三传取象均直接读取现有生产事实，不把六亲类型、
 * 旺衰、天将、亲疏、新旧或始终吉凶反过来收紧 matcher，亦不建立新的吉将/凶将模型。
 */
final class WuleiRule implements PanRule
{
    public const RULE_CODE = 'lesson.wulei';

    public const NAME = '物类课';

    public const GROUP = '六十四课';

    public const DESCRIPTION = '凡正常课取初传即成立；以初传为主辨六亲、五行、旺衰及物类，亲疏、新旧和始终吉凶等扩展暂按研究边界处理。';

    public const XIANG = '物以声应，方以类萃。六亲俱现，以用为主。旺相吉言，休囚凶语。始终吉凶，神将分取。';

    /** @var list<string> */
    public const UNCOVERED = [
        '六亲进一步细分到兄弟、姊妹、伯叔、姑、祖父母、妻妾、子孙、媒人等具体族类，尚未程序化。',
        '“阳神下临阳宫，有德合为亲；入阴宫为疏；阴神反之”的亲疏算法，因“德合”的精确机器语义尚未冻结，本轮不实现。',
        '刚日／柔日结合“干前、干后”判断未来、过去，尚未程序化。',
        '刚柔、阴阳、生死、旺衰以及“妇德从夫”判断新物、旧物的完整算法尚未冻结。',
        '猪、兔、羊、酒食及人物、六畜、物品等大型传统类神表不在第一版实现范围。',
        '“神将吉凶”不建立新的全局吉将／凶将公共模型。',
        '“初传旺相神将吉、末传囚死神将凶”等始终吉凶算法暂不实现，因为依赖尚未统一冻结的吉将／凶将、刑害、合德救制模型。',
        '《观月经》《心镜》等附录扩展只记录为旁证，不混入《六壬大全》主体 matcher。',
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
                ['code' => 'valid_initial', 'title' => '合法初传', 'description' => '只要排盘存在 0 至 11 范围内的合法初传，即成立物类课。'],
            ],
            'judgments' => [],
        ];
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $initial = $facts->get('sanchuan0');
        if (! is_int($initial) || $initial < 0 || $initial > 11) {
            return null;
        }

        $transmissions = [
            'initial' => $this->transmission($facts, 0),
            'middle' => $this->transmission($facts, 1),
            'final' => $this->transmission($facts, 2),
        ];

        return new RuleMatch(
            code: self::RULE_CODE,
            name: self::NAME,
            group: self::GROUP,
            description: self::DESCRIPTION,
            gua: '节',
            guaSymbol: '䷻',
            xiang: self::XIANG,
            evidence: [
                ...$transmissions,
                'transmissions' => $transmissions,
                'foundations' => [
                    ['code' => 'valid_initial', 'title' => '合法初传', 'description' => '凡正常课取初传即成立物类课。', 'matched' => true, 'evidence' => '当前初传为'.$transmissions['initial']['branch_name'].'。'],
                ],
                'judgments' => [],
                'uncovered' => self::UNCOVERED,
            ],
        );
    }

    /** @return array{branch: ?int, branch_name: ?string, element: ?int, element_name: ?string, liuqin: ?int, liuqin_name: ?string, seasonal_state: ?string, general: ?int, general_name: ?string} */
    private function transmission(PanFacts $facts, int $index): array
    {
        $branch = $facts->get('sanchuan'.$index);
        if (! is_int($branch) || $branch < 0 || $branch > 11) {
            return [
                'branch' => null, 'branch_name' => null, 'element' => null, 'element_name' => null,
                'liuqin' => null, 'liuqin_name' => null, 'seasonal_state' => null,
                'general' => null, 'general_name' => null,
            ];
        }

        $element = $facts->branchElement($branch);
        $liuqin = $facts->get('liuqin'.$index);
        $liuqin = is_int($liuqin) && array_key_exists($liuqin, PanCalculator::$liuqin) ? $liuqin : null;
        $general = $facts->generalRidingBranch($branch);

        return [
            'branch' => $branch,
            'branch_name' => PanCalculator::$dizhi[$branch] ?? null,
            'element' => $element,
            'element_name' => is_int($element) ? (PanCalculator::$wuxing[$element] ?? null) : null,
            'liuqin' => $liuqin,
            'liuqin_name' => is_int($liuqin) ? (PanCalculator::$liuqin[$liuqin] ?? null) : null,
            'seasonal_state' => $facts->branchSeasonalState($branch),
            'general' => $general,
            'general_name' => is_int($general) ? (PanCalculator::$tianjiang[$general] ?? null) : null,
        ];
    }
}
