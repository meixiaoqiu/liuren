<?php

namespace App\Domain\Pan\BiFa\Rules;

use App\Domain\Pan\BiFa\BiFaRule;
use App\Domain\Pan\BiFa\BiFaRuleMatch;
use App\Domain\Pan\Facts\PanFacts;
use App\Support\BiFaCatalog;
use LogicException;

/** 《六壬大全·毕法赋》第十法「朽木难雕别作为」。 */
final class XiuMuNanDiaoRule implements BiFaRule
{
    private const MAO = 3;

    private const ZHUOLUN_GROUNDS = [8, 9, 10];

    private const AXE_GROUNDS = [8, 9];

    private const ROUTES = [
        'rotten_wood' => ['朽木难雕', '斫轮发用而作为车轮的卯木本身旬空。'],
        'axe_unfavorable' => ['斧斤不利', '卯木本身不空，而所临申、酉刀斧之地旬空。'],
    ];

    public function code(): string
    {
        return 'bifa.10';
    }

    public function law(): array
    {
        $law = BiFaCatalog::findByCode($this->code());
        if ($law === null) {
            throw new LogicException('BiFaCatalog 找不到 '.$this->code().'；注册表与目录脱节。');
        }

        return $law;
    }

    public function definition(): array
    {
        return [
            'description' => '本法先以初传卯临申、酉、戌认定斫轮结构，再严格区分卯木本身旬空的「朽木难雕」与卯不空而所临申、酉金地旬空的「斧斤不利」。',
            'foundations' => array_map(
                static fn (array $route, string $code): array => [
                    'code' => $code,
                    'title' => $route[0],
                    'description' => $route[1],
                ],
                self::ROUTES,
                array_keys(self::ROUTES),
            ),
            'judgments' => [
                ['label' => '宜别作为', 'effect' => 'reduce', 'description' => '原文取象为朽木不可雕，宜改科易业、另谋营生。'],
                ['label' => '斧斤不利', 'effect' => 'reduce', 'description' => '木本身未空，而刀斧所临金地落空；原文断凡谋不遂。'],
            ],
            'sections' => [
                ['title' => '先辨斫轮结构', 'content' => '初传必须为卯，且卯实际临于申、酉、戌宫。单纯卯旬空而不具此结构，不成立本法。'],
                ['title' => '两格不可合并', 'content' => '卯木本身旬空才是朽木难雕；卯不空而所临申、酉金地旬空才是斧斤不利，正文明确后者并非朽木难雕。'],
                ['title' => '辛寄戌宫', 'content' => '卯加辛按辛所寄的戌宫判断，不要求当天必须为辛日；戌宫只参与朽木难雕，不把戌空推广为斧斤不利。'],
                ['title' => '本轮边界', 'content' => '不吸收斫轮课中的伤斧、伤轮、旧轮再斫，以及吉将、驿马、印绶等其他课义。'],
            ],
        ];
    }

    public function match(PanFacts $facts): ?BiFaRuleMatch
    {
        $initial = $facts->get('sanchuan0');
        $tianpan = $facts->get('tianpan');
        if ($initial !== self::MAO || ! $this->isValidPlate($tianpan)) {
            return null;
        }

        $maoGround = $facts->heavenBranchGroundPosition(self::MAO);
        if (! is_int($maoGround) || ! in_array($maoGround, self::ZHUOLUN_GROUNDS, true)) {
            return null;
        }

        $maoVoid = $facts->isBranchXunVoid(self::MAO);
        $maoGroundVoid = $facts->isBranchXunVoid($maoGround);
        if (! is_bool($maoVoid) || ! is_bool($maoGroundVoid)) {
            return null;
        }

        $route = match (true) {
            $maoVoid => 'rotten_wood',
            in_array($maoGround, self::AXE_GROUNDS, true) && $maoGroundVoid => 'axe_unfavorable',
            default => null,
        };
        if ($route === null) {
            return null;
        }

        [$title, $description] = self::ROUTES[$route];
        $judgment = $route === 'rotten_wood'
            ? ['label' => '宜别作为', 'effect' => 'reduce', 'description' => '原文取象为朽木不可雕，宜改科易业、另谋营生。']
            : ['label' => '斧斤不利', 'effect' => 'reduce', 'description' => '木本身未空，而刀斧所临金地落空；原文断凡谋不遂。'];
        $law = $this->law();

        return new BiFaRuleMatch(
            code: $this->code(), number: $law['number'], name: $law['name'], summary: $law['summary'],
            subMatches: [[
                'code' => $route,
                'title' => $title,
                'description' => $description,
                'matched' => true,
                'detail' => $description,
                'requires_people' => false,
                'people_missing' => false,
            ]],
            matchedRoutes: [$route],
            evidence: [
                'mao_ground' => $maoGround,
                'mao_ground_identity' => $maoGround,
                'mao_void' => $maoVoid,
                'mao_ground_void' => $maoGroundVoid,
                'is_zhuolun_position' => true,
            ],
            matchedJudgments: [$judgment],
        );
    }

    private function isValidPlate(mixed $tianpan): bool
    {
        if (! is_array($tianpan) || ! array_is_list($tianpan) || count($tianpan) !== 12) {
            return false;
        }

        foreach ($tianpan as $branch) {
            if (! is_int($branch) || $branch < 0 || $branch > 11) {
                return false;
            }
        }

        $unique = array_values(array_unique($tianpan, SORT_REGULAR));
        sort($unique);

        return $unique === range(0, 11);
    }
}
