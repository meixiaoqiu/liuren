<?php

namespace App\Extensions;

use App\Domain\Pan\BiFa\BiFaRule;

/**
 * 文件作用：聚合单个外部插件对毕法体系的最小扩展包。
 *
 *  四类贡献可由外部插件一次性提交，不要求全部提供：
 *
 *   - rules             :list<BiFaRule>              注入 BiFaRuleEngine 的判定器；
 *   - summaries         :array<string,string>        按 code 覆盖目录中的简介；
 *   - cases             :array<string,list<array>>   按 law_code 追加案例；
 *   - researchDocuments :list<array{...}>            按 number 注册研究文档（可指向仓库外绝对路径）。
 *
 * 本类是数据传输容器，不调用任何 ServiceProvider 或 Container——
 * 由调用方（如外部插件的 ServiceProvider）构造后再交给 BiFaExtensionRegistry::contribute()。
 */
final readonly class BiFaContribution
{
    /**
     * @param  list<BiFaRule>  $rules
     * @param  array<string, string>  $summaries  按 BiFaRule code 覆盖 BiFaCatalog 中的简介
     * @param  array<string, list<array<string, mixed>>>  $cases  按 BiFaRule code 追加的案例
     * @param  list<array{number: int, filename: string, path: string, research_url?: ?string}>  $researchDocuments
     *                                                                                                               path 允许相对（相对宿主 base_path）或绝对；AbsolutePath 已统一处理。
     */
    public function __construct(
        public array $rules = [],
        public array $summaries = [],
        public array $cases = [],
        public array $researchDocuments = [],
    ) {}
}
