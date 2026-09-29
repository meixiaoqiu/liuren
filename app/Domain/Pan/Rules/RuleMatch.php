<?php

namespace App\Domain\Pan\Rules;

/** 文件作用：承载单条规则命中后的编码、分类标识、卦名、卦符、名称、说明、象、证据和覆盖范围。 */
final readonly class RuleMatch
{
    /** 六十四课（课经）默认分类标识；格、传等非默认分类由各规则显式覆写。 */
    public const DEFAULT_MARKER = '经';

    public readonly string $marker;

    /** 是否为主课（未显式覆写 marker，回落至默认分类「经」），用于前台将主课排在格、传之前。 */
    public readonly bool $isPrimary;

    /**
     * @param  array<string, mixed>  $evidence
     * @param  list<string>  $coverageAreas
     */
    public function __construct(
        public string $code,
        public string $name,
        public string $group,
        public string $description,
        ?string $marker = null,
        public ?string $gua = null,
        public ?string $guaSymbol = null,
        public ?string $xiang = null,
        public array $evidence = [],
        public array $coverageAreas = [],
    ) {
        $this->marker = $marker ?? self::DEFAULT_MARKER;
        $this->isPrimary = $marker === null;
    }

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
