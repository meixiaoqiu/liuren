<?php

namespace App\Domain\Pan\BiFa\Rules;

use App\Domain\Pan\BiFa\BiFaRule;
use App\Domain\Pan\BiFa\BiFaRuleMatch;
use App\Domain\Pan\Facts\PanFacts;
use App\Support\BiFaCatalog;
use LogicException;

/** 《六壬大全·毕法赋》第六法「六阴相继尽昏迷」。 */
final class LiuYinXiangJiRule implements BiFaRule
{
    private const BRANCH_NAMES = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];

    private const REDUCTIONS = [
        '出户' => [1, 3, 5],
        '盈阳' => [3, 5, 7],
        '励明' => [9, 7, 5],
        '回明' => [7, 5, 3],
    ];

    public function code(): string
    {
        return 'bifa.06';
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
            'description' => '四课四上神与中、末传六位全阴，或六位恰五阴一阳而占人本命、行年得阴填实，或四课逐课下生上且三传连续相生，皆属第六法。',
            'foundations' => [
                ['code' => 'six_yin', 'title' => '六阴格', 'description' => '四课四上神、中传、末传六个位置全部属阴；允许重复，不要求六种阴支齐全。'],
                ['code' => 'five_yin_filled_by_person', 'title' => '五阴年命填实', 'description' => '六位恰五阴一阳，且占人本命或行年本身属阴。'],
                ['code' => 'source_exhausted_root_severed', 'title' => '源消根断格', 'description' => '四课逐课下神生上神，同时初传生中传、中传生末传。'],
            ],
            'judgments' => [
                ['label' => '昏迷不明', 'effect' => 'neutral', 'description' => '六阴相继，事情多见昏暗不明、进退难决。'],
                ['label' => '五阴年命填实', 'effect' => 'neutral', 'description' => '六位已有五阴，又得占人阴支本命或行年填实，亦主事情幽暗、难以明察。'],
                ['label' => '自昼传夜', 'effect' => 'increase', 'description' => '初传在昼位、末传入夜位，昏迷愈甚。'],
                ['label' => '出户、盈阳、励明、回明', 'effect' => 'reduce', 'description' => '三传符合减损格时，未可以昏迷断之，凶中有吉。'],
                ['label' => '源消根断', 'effect' => 'neutral', 'description' => '根源不断向外泄生，凡占多主脱耗、日渐消铄。'],
            ],
            'sections' => [
                ['title' => '六位的确定', 'content' => '六位固定取四课四个上神与中传、末传。初传来自四课上神之一，不重复计入；判断位置阴阳，允许地支重复。'],
                ['title' => '五阴与年命', 'content' => '五阴只要求六位恰五阴一阳，并由占人本命或行年中的阴支填实，不要求补某个特定地支。人物资料不足时保留为待评估。'],
                ['title' => '加重与减损', 'content' => '自昼传夜只看初、末传所在昼夜方位。出户、盈阳、励明、回明严格按三传完整顺序判断，只减损六阴的昏迷断义，不取消六阴成立。'],
                ['title' => '源消根断的严格口径', 'content' => '本项目采用《六壬大全》口径：四课全部下生上，且三传继续初生中、中生末；《大全》明称止四日四课。后世仅以四课下生上立格的扩大解释不进入判定。'],
            ],
        ];
    }

    public function match(PanFacts $facts): ?BiFaRuleMatch
    {
        $sike = $facts->get('sike');
        $transmissions = [$facts->get('sanchuan0'), $facts->get('sanchuan1'), $facts->get('sanchuan2')];
        if (! is_array($sike) || count($sike) < 8 || ! self::allBranches($transmissions)) {
            return null;
        }

        $lowers = [];
        $uppers = [];
        foreach ([[0, 1], [2, 3], [4, 5], [6, 7]] as $lessonIndex => [$lowerIndex, $upperIndex]) {
            $lower = $sike[$lowerIndex] ?? null;
            $upper = $sike[$upperIndex] ?? null;
            if (($lessonIndex === 0 ? ! self::isStem($lower) : ! self::isBranch($lower)) || ! self::isBranch($upper)) {
                return null;
            }
            $lowers[] = $lower;
            $uppers[] = $upper;
        }

        [$initial, $middle, $final] = $transmissions;
        $positions = [...$uppers, $middle, $final];
        $yinCount = count(array_filter($positions, static fn (int $branch): bool => $branch % 2 === 1));
        $person = self::resolvePerson($facts);
        $personYin = $person !== null && (($person['nianming'] !== null && $person['nianming'] % 2 === 1)
            || ($person['xingnian'] !== null && $person['xingnian'] % 2 === 1));
        $personMissing = $person === null || ($person['nianming'] === null && $person['xingnian'] === null);
        $personPartlyMissing = $person !== null && ($person['nianming'] === null || $person['xingnian'] === null);

        $sixYin = $yinCount === 6;
        $fiveYinCandidate = $yinCount === 5;
        $fiveYin = $fiveYinCandidate && $personYin;
        $fiveYinPending = $fiveYinCandidate && ! $personYin && ($personMissing || $personPartlyMissing);
        $lessonGenerations = [];
        foreach ($lowers as $index => $lower) {
            $lessonGenerations[] = self::generatesElements(
                $index === 0 ? $facts->stemElement($lower) : $facts->branchElement($lower),
                $facts->branchElement($uppers[$index]),
            );
        }
        $transmissionGenerations = [
            self::generates($facts, $initial, $middle),
            self::generates($facts, $middle, $final),
        ];
        $sourceExhausted = ! in_array(false, $lessonGenerations, true)
            && ! in_array(false, $transmissionGenerations, true);

        $subMatches = [
            self::subMatch('six_yin', '六阴格', $sixYin, false, false, $sixYin ? '四课四上神与中、末传共六位皆为阴支。' : null),
            self::subMatch('five_yin_filled_by_person', '五阴年命填实', $fiveYin, true, $fiveYinPending, $fiveYin ? self::personEvidence($person) : null),
            self::subMatch('source_exhausted_root_severed', '源消根断格', $sourceExhausted, false, false, $sourceExhausted ? '四课逐课下生上，三传又连续初生中、中生末。' : null),
        ];
        $matchedRoutes = array_values(array_map(
            static fn (array $sub): string => $sub['code'],
            array_filter($subMatches, static fn (array $sub): bool => $sub['matched']),
        ));
        $pendingRoutes = $fiveYinPending ? ['five_yin_filled_by_person'] : [];
        if ($matchedRoutes === [] && $pendingRoutes === []) {
            return null;
        }

        $judgments = [];
        $dayToNight = in_array($initial, [3, 4, 5, 6, 7, 8], true)
            && in_array($final, [9, 10, 11, 0, 1, 2], true);
        if ($sixYin && $dayToNight) {
            $judgments[] = ['label' => '自昼传夜', 'effect' => 'increase', 'description' => '自昼传夜，昏迷愈甚。'];
        }
        $reduction = $sixYin ? array_search($transmissions, self::REDUCTIONS, true) : false;
        if (is_string($reduction)) {
            $judgments[] = ['label' => $reduction, 'effect' => 'reduce', 'description' => '未可以昏迷断之，凶中有吉。'];
        }
        if ($sourceExhausted) {
            $judgments[] = ['label' => '源消根断', 'effect' => 'neutral', 'description' => '根源不断向外泄生，凡占多主脱耗、日渐消铄。'];
        }

        $law = $this->law();

        return new BiFaRuleMatch(
            code: $this->code(), number: $law['number'], name: $law['name'], summary: $law['summary'],
            subMatches: $subMatches, matchedRoutes: $matchedRoutes, pendingRoutes: $pendingRoutes,
            evidence: [
                'lesson_lowers' => $lowers, 'lesson_uppers' => $uppers, 'positions' => $positions,
                'yin_count' => $yinCount, 'person' => $person,
                'lesson_generations' => $lessonGenerations,
                'transmission_generations' => $transmissionGenerations,
                'day_to_night' => $dayToNight, 'reduction' => $reduction === false ? null : $reduction,
            ],
            matchedJudgments: $judgments,
        );
    }

    private static function generates(PanFacts $facts, int $source, int $target): bool
    {
        return self::generatesElements($facts->branchElement($source), $facts->branchElement($target));
    }

    private static function generatesElements(?int $source, ?int $target): bool
    {
        return $source !== null && $target !== null && ($source + 1) % 5 === $target;
    }

    private static function isStem(mixed $value): bool
    {
        return is_int($value) && $value >= 0 && $value <= 9;
    }

    private static function isBranch(mixed $value): bool
    {
        return is_int($value) && $value >= 0 && $value <= 11;
    }

    private static function allBranches(array $values): bool
    {
        return count($values) === 3 && count(array_filter($values, self::isBranch(...))) === 3;
    }

    /** @return array{nianming: ?int, xingnian: ?int}|null */
    private static function resolvePerson(PanFacts $facts): ?array
    {
        $person = $facts->personByRole('querent');
        if ($person === null) {
            return null;
        }

        return [
            'nianming' => self::isBranch($person['nianming'] ?? null) ? $person['nianming'] : null,
            'xingnian' => self::isBranch($person['xingnian'] ?? null) ? $person['xingnian'] : null,
        ];
    }

    private static function personEvidence(?array $person): string
    {
        $hits = [];
        foreach (['nianming' => '本命', 'xingnian' => '行年'] as $key => $label) {
            if (is_int($person[$key] ?? null) && $person[$key] % 2 === 1) {
                $hits[] = $label.self::BRANCH_NAMES[$person[$key]];
            }
        }

        return '六个检查位恰五阴一阳，'.implode('、', $hits).'为阴支，以年命填实。';
    }

    private static function subMatch(string $code, string $title, bool $matched, bool $requiresPeople, bool $peopleMissing, ?string $detail): array
    {
        $descriptions = [
            'six_yin' => '六个阴阳位置全部属阴。',
            'five_yin_filled_by_person' => '六位恰五阴一阳，以占人阴支年命填实。',
            'source_exhausted_root_severed' => '四课逐课下生上，三传继续连续相生。',
        ];

        return [
            'code' => $code, 'title' => $title, 'description' => $descriptions[$code],
            'matched' => $matched, 'detail' => $detail,
            'requires_people' => $requiresPeople, 'people_missing' => $peopleMissing,
        ];
    }
}
