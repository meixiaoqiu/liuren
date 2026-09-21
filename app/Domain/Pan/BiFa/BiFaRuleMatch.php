<?php

namespace App\Domain\Pan\BiFa;

/**
 * 文件作用：承载一条毕法命中后的 code、name、description、命中分格及分格证据。
 *
 * 与 RuleMatch 相比，本类刻意不引入 group / gua / guaSymbol / xiang / marker：
 *
 *  - 毕法不以"卦体"立名；
 *  - 毕法不参与"主课 / 格 / 传"的标记分类；
 *  - 一张盘可以同时命中同一毕法的多个分格，因此 evidence 必须保留完整的 matched_routes
 *    与每个分格的命中证据，页面才能逐条展示"✓ 引从天干 ……" 等清单。
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
     * @param  array<string, mixed>  $evidence
     */
    public function __construct(
        public string $code,
        public string $name,
        public string $summary,
        public array $subMatches,
        public array $matchedRoutes,
        public array $evidence = [],
    ) {}

    /**
     * @return array{
        code: string,
        name: string,
        summary: string,
        matched: bool,
        sub_matches: list<SubMatch>,
        matched_routes: list<string>,
        evidence: array<string, mixed>
     * }
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'summary' => $this->summary,
            'matched' => $this->matchedRoutes !== [],
            'sub_matches' => $this->subMatches,
            'matched_routes' => $this->matchedRoutes,
            'evidence' => $this->evidence,
        ];
    }
}