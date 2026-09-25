<?php

namespace App\Domain\Pan\BiFa\Rules;

use App\Domain\Pan\BiFa\BiFaRule;
use App\Domain\Pan\BiFa\BiFaRuleMatch;
use App\Domain\Pan\Facts\PanFacts;
use App\Support\BiFaCatalog;
use LogicException;

/** 《六壬大全·毕法赋》第九法「避难逃生须弃旧」。 */
final class BiNanTaoShengRule implements BiFaRule
{
    /** 十干长生、墓、禄；本法沿用六壬口径，不引入阴干逆行十二长生。 */
    private const DAY_ORIGIN = [11, 11, 2, 2, 8, 8, 5, 5, 8, 8];

    private const DAY_GRAVE = [7, 7, 10, 10, 4, 4, 1, 1, 4, 4];

    private const DAY_LU = [0 => 2, 1 => 3, 2 => 5, 3 => 6, 4 => 5, 5 => 6, 6 => 8, 7 => 9, 8 => 11, 9 => 0];

    /** 木、火、土、金、水的长生地。 */
    private const ELEMENT_ORIGIN = [0 => 11, 1 => 2, 2 => 8, 3 => 5, 4 => 8];

    private const ROUTES = [
        'escape_to_stem_support' => ['就干上之生', '三传俱有正文明确不利之处，转就不空而能生日干的干上神。'],
        'escape_to_branch_support' => ['就支上之生', '三传俱无益，日干寄宫之支加临日支，且日支生日干。'],
        'escape_to_ground_support' => ['日干坐地盘之生', '三传俱无益，日干寄宫之支所坐地盘生日干，且该地盘不是日支。'],
        'fate_ding_on_growth' => ['本命乘丁坐长生', '占者本命为本旬丁神，且该丁神所坐地盘正为其五行长生地。'],
        'escape_to_wealth' => ['日干下临财乡', '三传俱无益，日干寄宫之支所坐地盘为日干之财。'],
        'escape_failed' => ['避难逃生而终不能逃生', '干上为日墓，初传为日禄且旬空，中传复墓，末传虽长生却乘白虎。'],
        'abandon_benefit_for_loss' => ['舍益就损', '干上已有不空的生神或长生，却使日干寄宫之支加临日支，而日支又为脱、鬼或墓。'],
        'neither_stay_nor_leave' => ['舍就皆不可', '干上生日干而旬空，日干寄宫之支又加临使日干受脱或受鬼的日支。'],
        'grave_as_sun' => ['墓作太阳', '日墓覆干，而该墓神同时正是当前月将。'],
    ];

    public function code(): string
    {
        return 'bifa.09';
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
            'description' => '这是「舍何处、就何处」的趋避原则，不应简单显示为「本盘吉」或「本盘凶」。五种正向路径与四种变格分别计算，可并见而不互相吞并。',
            'foundations' => array_map(
                static fn (string $code, array $meta): array => ['code' => $code, 'title' => $meta[0], 'description' => $meta[1]],
                array_keys(self::ROUTES), self::ROUTES,
            ),
            'judgments' => [
                ['label' => '逃生受阻', 'effect' => 'reduce', 'description' => '原有之处为墓，投禄而禄空，再遇墓，最终长生又乘白虎，虽寻生路仍不能真正脱困。'],
                ['label' => '难中有援', 'effect' => 'increase', 'description' => '墓神覆日，却同时为太阳、月将，主处难之中有上人提携之象。'],
            ],
            'sections' => [
                ['title' => '五种避难能逃生', 'content' => '就干上之生、就支上之生、日干坐地盘之生、本命乘丁坐长生、日干下临财乡，各自是独立路径。'],
                ['title' => '避难逃生而终不能逃生', 'content' => '严格依正文丁亥例的墓、空禄、复墓、长生乘白虎结构识别，不向其他凶象泛化。'],
                ['title' => '舍益就损', 'content' => '明明干上有可守之益，却转就日支的脱、鬼或墓。乙酉、辛丑依《壬学琐记》校勘归入此格。'],
                ['title' => '舍就皆不可', 'content' => '留下的生处已空，出去又受脱或受克；只以庚子、庚午所代表的校勘后结构抽象。'],
                ['title' => '墓作太阳', 'content' => '墓神覆日且正作月将，是独立救解格，不归入五种避难逃生路径。'],
            ],
        ];
    }

    public function match(PanFacts $facts): ?BiFaRuleMatch
    {
        $stem = $facts->get('rigan');
        $branch = $facts->get('rizhi');
        $tianpan = $facts->get('tianpan');
        if (! is_int($stem) || ! isset(self::DAY_ORIGIN[$stem], self::DAY_GRAVE[$stem], self::DAY_LU[$stem])
            || ! self::isBranch($branch) || ! is_array($tianpan)) {
            return null;
        }

        $lodging = $facts->stemLodgingBranch($stem);
        $stemUpper = is_int($lodging) && self::isBranch($tianpan[$lodging] ?? null) ? $tianpan[$lodging] : null;
        if ($lodging === null || $stemUpper === null) {
            return null;
        }

        $stemElement = $facts->stemElement($stem);
        $stemUpperElement = $facts->branchElement($stemUpper);
        $lodgingGround = $facts->heavenBranchGroundPosition($lodging);
        $groundElement = is_int($lodgingGround) ? $facts->branchElement($lodgingGround) : null;
        if ($stemElement === null) {
            return null;
        }

        $transmissionReasons = $this->explicitAdverseReasonsForTransmissions($facts, $stem, $stemElement);
        $threeUnhelpful = count($transmissionReasons) === 3
            && ! in_array([], $transmissionReasons, true);
        $stemSupports = $stemUpperElement === ($stemElement + 4) % 5;
        $stemSupportUsable = $stemSupports && $facts->isBranchXunVoid($stemUpper) === false;
        $stemBenefitUsable = ($stemSupports || $stemUpper === self::DAY_ORIGIN[$stem])
            && $facts->isBranchXunVoid($stemUpper) === false;
        $lodgingOnBranch = $lodgingGround === $branch;
        $branchElement = $facts->branchElement($branch);

        $matched = [
            'escape_to_stem_support' => $threeUnhelpful && $stemSupportUsable,
            'escape_to_branch_support' => $threeUnhelpful && $lodgingOnBranch && $branchElement === ($stemElement + 4) % 5,
            'escape_to_ground_support' => $threeUnhelpful && is_int($lodgingGround) && $lodgingGround !== $branch
                && $groundElement === ($stemElement + 4) % 5,
            'fate_ding_on_growth' => false,
            'escape_to_wealth' => $threeUnhelpful && is_int($lodgingGround) && $facts->isDayWealthBranch($lodgingGround),
            'escape_failed' => $stemUpper === self::DAY_GRAVE[$stem]
                && $facts->get('sanchuan0') === self::DAY_LU[$stem]
                && $facts->isBranchXunVoid(self::DAY_LU[$stem]) === true
                && $facts->get('sanchuan1') === self::DAY_GRAVE[$stem]
                && $facts->get('sanchuan2') === self::DAY_ORIGIN[$stem]
                && $facts->generalRidingBranch(self::DAY_ORIGIN[$stem]) === 7,
            'abandon_benefit_for_loss' => $stemBenefitUsable
                && $lodgingOnBranch
                && ($branchElement === ($stemElement + 1) % 5
                    || $branchElement === ($stemElement + 3) % 5
                    || $branch === self::DAY_GRAVE[$stem]),
            'neither_stay_nor_leave' => $stemSupports
                && $facts->isBranchXunVoid($stemUpper) === true
                && $lodgingOnBranch
                && ($branchElement === ($stemElement + 1) % 5 || $branchElement === ($stemElement + 3) % 5),
            'grave_as_sun' => $stemUpper === self::DAY_GRAVE[$stem] && $facts->get('yuejiang') === $stemUpper,
        ];

        $xunHead = $facts->dayXunHeadBranch();
        $xunDing = $xunHead === null ? null : ($xunHead + 3) % 12;
        $xunDingGround = is_int($xunDing) ? $facts->heavenBranchGroundPosition($xunDing) : null;
        $xunDingElement = is_int($xunDing) ? $facts->branchElement($xunDing) : null;
        $xunDingGrowth = is_int($xunDingElement) ? self::ELEMENT_ORIGIN[$xunDingElement] : null;
        $dingOnGrowth = is_int($xunDingGround) && $xunDingGround === $xunDingGrowth;
        $person = $facts->personByRole('querent');
        $nianming = is_array($person) && self::isBranch($person['nianming'] ?? null) ? $person['nianming'] : null;
        $peopleMissing = $dingOnGrowth && $nianming === null;
        $matched['fate_ding_on_growth'] = $dingOnGrowth && $nianming === $xunDing;

        $matchedRoutes = array_keys(array_filter($matched));
        $pendingRoutes = $peopleMissing ? ['fate_ding_on_growth'] : [];
        if ($matchedRoutes === [] && $pendingRoutes === []) {
            return null;
        }

        $subMatches = [];
        foreach (self::ROUTES as $code => [$title, $description]) {
            $subMatches[] = [
                'code' => $code, 'title' => $title, 'description' => $description,
                'matched' => $matched[$code],
                'detail' => $matched[$code] ? $description : ($code === 'fate_ding_on_growth' && $peopleMissing ? '丁神已坐长生，但缺少占者本命，待评估本命是否为丁神。' : null),
                'requires_people' => $code === 'fate_ding_on_growth',
                'people_missing' => $code === 'fate_ding_on_growth' && $peopleMissing,
            ];
        }

        $judgments = [];
        if ($matched['escape_failed']) {
            $judgments[] = $this->definition()['judgments'][0];
        }
        if ($matched['grave_as_sun']) {
            $judgments[] = $this->definition()['judgments'][1];
        }

        $law = $this->law();

        return new BiFaRuleMatch(
            code: $this->code(), number: $law['number'], name: $law['name'], summary: $law['summary'],
            subMatches: $subMatches, matchedRoutes: $matchedRoutes, pendingRoutes: $pendingRoutes,
            evidence: [
                'stem_upper' => $stemUpper, 'lodging_branch' => $lodging, 'lodging_ground' => $lodgingGround,
                'transmission_adverse_reasons' => $transmissionReasons, 'three_transmissions_explicitly_unhelpful' => $threeUnhelpful,
                'xun_ding' => $xunDing, 'xun_ding_ground' => $xunDingGround, 'xun_ding_growth' => $xunDingGrowth,
            ],
            matchedJudgments: $judgments,
        );
    }

    /** @return list<list<string>> */
    private function explicitAdverseReasonsForTransmissions(PanFacts $facts, int $stem, int $stemElement): array
    {
        $result = [];
        foreach (['sanchuan0', 'sanchuan1', 'sanchuan2'] as $key) {
            $transmission = $facts->get($key);
            if (! self::isBranch($transmission)) {
                return [];
            }
            $element = $facts->branchElement($transmission);
            $reasons = [];
            if ($facts->isBranchXunVoid($transmission) === true) {
                $reasons[] = '旬空';
            }
            if ($element === ($stemElement + 3) % 5) {
                $reasons[] = '日鬼';
            }
            if ($element === ($stemElement + 1) % 5) {
                $reasons[] = '脱气';
            }
            if ($transmission === self::DAY_GRAVE[$stem]) {
                $reasons[] = '日墓';
            }
            if ($facts->isDayWealthBranch($transmission) && $this->wealthControlledAboveAndBelow($facts, $transmission)) {
                $reasons[] = '财受上下夹克';
            }
            $result[] = $reasons;
        }

        return $result;
    }

    private function wealthControlledAboveAndBelow(PanFacts $facts, int $wealth): bool
    {
        $wealthElement = $facts->branchElement($wealth);
        $ground = $facts->heavenBranchGroundPosition($wealth);
        $groundElement = is_int($ground) ? $facts->branchElement($ground) : null;
        $general = $facts->generalRidingBranch($wealth);
        $generalElement = is_int($general) ? $facts->generalElement($general) : null;

        return $wealthElement !== null && $groundElement !== null && $generalElement !== null
            && $wealthElement === ($groundElement + 2) % 5
            && $wealthElement === ($generalElement + 2) % 5;
    }

    private static function isBranch(mixed $value): bool
    {
        return is_int($value) && $value >= 0 && $value <= 11;
    }
}
