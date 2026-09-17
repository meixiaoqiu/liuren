<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/** 文件作用：将间传课成立后三传唯一确定的二十四格接入项目既有课体机制；不参与间传主体判断。 */
final readonly class JianchuanGridRule implements PanRule
{
    use LessonDefinitionDefaults;

    /** @param array{code:string,label:string,description:string} $definition */
    public function __construct(
        private string $sequence,
        private array $definition,
    ) {}

    /** @return list<self> */
    public static function all(): array
    {
        return array_map(
            static fn (string $sequence, array $definition): self => new self($sequence, $definition),
            array_keys(JianchuanRule::SUBTYPES),
            array_values(JianchuanRule::SUBTYPES),
        );
    }

    public function code(): string
    {
        return 'structure.jianchuan_'.$this->definition['code'];
    }

    /** @return array{code:string,name:string,group:string,marker:string,description:string} */
    public function gridDefinition(): array
    {
        return [
            'code' => $this->code(),
            'name' => $this->definition['label'],
            'group' => '间传课体',
            'marker' => '格',
            'description' => $this->definition['description'],
        ];
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $transmissions = array_map(
            static fn (string $key): mixed => $facts->get($key),
            ['sanchuan0', 'sanchuan1', 'sanchuan2'],
        );

        if ($transmissions !== array_map('intval', explode(',', $this->sequence))) {
            return null;
        }

        $names = implode('、', array_map(
            static fn (int $branch): string => PanCalculator::$dizhi[$branch] ?? '?',
            $transmissions,
        ));

        return new RuleMatch(
            code: $this->code(),
            name: $this->definition['label'],
            group: '间传课体',
            description: $this->definition['description'],
            marker: '格',
            evidence: ['detail' => "三传{$names}唯一对应{$this->definition['label']}。"],
        );
    }
}
