<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：为全局课下润下、炎上、曲直、从革、稼穑五格提供项目既有“格”RuleMatch骨架。 */
abstract class QuanjuGridRule implements PanRule
{
    use LessonDefinitionDefaults;

    protected const SLUG = '';

    protected const NAME = '';

    protected const DESCRIPTION = '';

    protected const GROUP = '全局课体';

    protected const MARKER = '格';

    public function code(): string
    {
        return 'structure.'.static::SLUG;
    }

    /** @return array{code:string,name:string,group:string,marker:string,description:string} */
    public function gridDefinition(): array
    {
        return [
            'code' => $this->code(),
            'name' => static::NAME,
            'group' => static::GROUP,
            'marker' => static::MARKER,
            'description' => static::DESCRIPTION,
        ];
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $grid = QuanjuSupport::classify($facts);
        if ($grid === null || $grid['slug'] !== static::SLUG) {
            return null;
        }

        return new RuleMatch(
            code: $this->code(),
            name: static::NAME,
            group: static::GROUP,
            description: static::DESCRIPTION,
            marker: static::MARKER,
            evidence: ['detail' => QuanjuSupport::detail($grid)],
        );
    }
}
