<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：聚合繁昌课下的德孕格与旺孕格；任一格命中即成立繁昌课。 */
final class FanchangRule implements ContextAwareRule
{
    use FanchangSupport;

    protected const RULE_CODE = 'lesson.fanchang';

    protected const NAME = '繁昌课';

    protected const GROUP = '六十四课';

    protected const DESCRIPTION = '夫妻行年干支相合为德孕格，或夫妻行年地支三合且俱得季节旺相为旺孕格。';

    protected const GUA = '咸';

    protected const GUA_SYMBOL = '䷞';

    protected const XIANG = '阴阳和合，万物生成，命招贵孕，娠必男形，谋为大利，家道自兴。如逢互克，分散零丁。';

    /** @var list<string> 尚未覆盖的原文判断项 */
    private const UNCOVERED = [
        '《六壬大全》正文课例另要求"夫命水、妻命金、各乘本命旺气"等附加结构：本实现按《观月经》德孕段仅取干支相合两条必要条件，附加结构以课经笔记方式保留，不作为德孕格通用必要条件',
        '《大全》"德方发用"确切含义未定，暂不解释为初传条件',
        '"如逢互克，分散零丁"的凶象（夫妻行年互克）未实现',
        '行年值败、绝、刑、害为德孕不育（未实现）',
        '产期法（取妻年位上神前三位为生月等）未实现',
        '生子善恶情性（丙辛合生黑子等）未实现',
    ];

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function requiredContext(): array
    {
        return ['people.querent', 'people.spouse'];
    }

    public function notEvaluatedInfo(): array
    {
        return [
            'name' => self::NAME,
            'notice' => '需要配偶出生信息，当前未进行判断。',
        ];
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $deYunMatch = (new DeYunRule)->match($facts);
        $wangYunMatch = (new WangYunRule)->match($facts);

        $foundations = [];

        if ($deYunMatch !== null) {
            $foundations[] = [
                'title' => '德孕格',
                'detail' => $deYunMatch->evidence['detail'] ?? '德孕格成立。',
            ];
        }

        if ($wangYunMatch !== null) {
            $foundations[] = [
                'title' => '旺孕格',
                'detail' => $wangYunMatch->evidence['detail'] ?? '旺孕格成立。',
            ];
        }

        if ($foundations === []) {
            return null;
        }

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
                'uncovered' => self::UNCOVERED,
            ],
        );
    }
}
