<?php

namespace App\Domain\Pan\BiFa;

/**
 * 文件作用：承载一条毕法命中后的法号、法名、code、目录简介、命中分格与命中分格证据。
 *
 * 与 RuleMatch 相比，本类刻意不引入 group / gua / guaSymbol / xian / marker：
 *
 *  - 毕法不以"卦体"立名；
 *  - 毕法不参与"主课 / 格 / 传"的标记分类；
 *  - 一张盘可以同时命中同一毕法的多个分格，因此 evidence 必须保留完整的 matched_routes
 *    与每个分格的命中证据，页面才能逐条展示"✓ 引从天干 ……" 等清单。
 *
 * `number` 字段输出到 UI 用于渲染"第 N 法"——不得用 `code`（`bifa.NN`）冒充法号。
 *
 * @phpstan-type SubMatch array{
 *     code: string,
 *     title: string,
 *     description: string,
 *     matched: bool,
 *     detail: ?string,
 *     requires_people: bool,
 *     people_missing: bool
 * }
 */
final readonly class BiFaRuleMatch
{
    /**
     * @param  list<SubMatch>  $subMatches
     * @param  list<string>  $matchedRoutes
     * @param  list<string>  $pendingRoutes  满足前置条件但因人物资料缺失而未评估的 route
     * @param  array<string, mixed>  $evidence
     */
    public function __construct(
        public string $code,
        public int $number,
        public string $name,
        public string $summary,
        public array $subMatches,
        public array $matchedRoutes,
        public array $pendingRoutes = [],
        public array $evidence = [],
    ) {}

    /**
     * @return array{
        code: string,
        number: int,
        name: string,
        summary: string,
        matched: bool,
        sub_matches: list<SubMatch>,
        matched_routes: list<string>,
        pending_routes: list<string>,
        evidence: array<string, mixed>
     * }
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'number' => $this->number,
            'name' => $this->name,
            'summary' => $this->summary,
            'matched' => $this->matchedRoutes !== [],
            'sub_matches' => $this->subMatches,
            'matched_routes' => $this->matchedRoutes,
            'pending_routes' => $this->pendingRoutes,
            'evidence' => $this->evidence,
        ];
    }
}
