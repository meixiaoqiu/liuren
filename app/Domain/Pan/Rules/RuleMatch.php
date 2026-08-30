<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：承载单条规则命中后的编码、分类标识、卦名、卦符、名称、说明、象、证据和覆盖范围。 */
final readonly class RuleMatch
{
    /**
     * @param  array<string, mixed>  $evidence
     * @param  list<string>  $coverageAreas
     */
    public function __construct(
        public string $code,
        public string $name,
        public string $group,
        public string $description,
        public string $marker = '课',
        public ?string $gua = null,
        public ?string $guaSymbol = null,
        public ?string $xiang = null,
        public array $evidence = [],
        public array $coverageAreas = [],
    ) {}

    /** @return array{code: string, name: string, group: string, description: string, marker: string, gua: ?string, guaSymbol: ?string, xiang: ?string, evidence: array<string, mixed>, coverageAreas: list<string>} */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'group' => $this->group,
            'description' => $this->description,
            'marker' => $this->marker,
            'gua' => $this->gua,
            'guaSymbol' => $this->guaSymbol,
            'xiang' => $this->xiang,
            'evidence' => $this->evidence,
            'coverageAreas' => $this->coverageAreas,
        ];
    }
}
