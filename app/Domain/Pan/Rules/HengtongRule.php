<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;

/** 文件作用：按《六壬大全》判断三传递生日干，或干支上下神互生俱生、互旺俱旺所成的亨通课。 */
final class HengtongRule implements PanRule
{
    use HengtongSupport;

    protected const RULE_CODE = 'lesson.hengtong';

    protected const NAME = '亨通课';

    protected const GROUP = '六十四课';

    protected const DESCRIPTION = '三传递生日干，或干支上下神互生俱生、互旺俱旺。';

    protected const GUA = '渐';

    protected const GUA_SYMBOL = '䷴';

    protected const XIANG = '三传相生，干支有情，官逢荐擢，士获科名，婚姻合和，财利生成，经营诸事，贵人欢迎。';

    /** @var list<string> 尚未覆盖的原文判断项 */
    private const UNCOVERED = [
        '末助初传生日（“末助初传生日，主傍人暗助吹嘘”，未实现）',
        '末助初传作日财（“主暗地人以财相助”，未实现）',
        '支加干生日为自在格（“主人来资助於我”，未实现）',
        '递生值空亡、破、刑、克、害而无解救为凶（未实现）',
        '初生中、中生末、末克日干为恩多怨深（未实现）',
        '干支俱旺及旺禄、传财逢空，羊刃变为罗网缠身（未实现）',
        '六处有冲为破罗破网（未实现）',
    ];

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $grids = [
            ['title' => '递生格', 'detail' => '三传递生日干。', 'matched' => self::diShengDetail($facts) !== null],
            ['title' => '俱生格', 'detail' => '干上生干、支上生支。', 'matched' => self::juShengDetail($facts) !== null],
            ['title' => '互生格', 'detail' => '干上生支、支上生干。', 'matched' => self::huShengDetail($facts) !== null],
            ['title' => '俱旺格', 'detail' => '干上为干旺神、支上为支旺神。', 'matched' => self::juWangDetail($facts) !== null],
            ['title' => '互旺格', 'detail' => '干上为支旺神、支上为干旺神。', 'matched' => self::huWangDetail($facts) !== null],
        ];

        $foundations = array_filter(
            $grids,
            fn (array $grid): bool => $grid['matched'],
        );

        if ($foundations === []) {
            return null;
        }

        $foundations = array_values(array_map(
            fn (array $grid): array => ['title' => $grid['title'], 'detail' => $grid['detail']],
            $foundations,
        ));

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
