<?php

namespace App\Domain\Pan\BiFa\Rules;

use App\Domain\Pan\BiFa\BiFaRule;
use App\Domain\Pan\BiFa\BiFaRuleMatch;
use App\Domain\Pan\Facts\PanFacts;
use App\Support\BiFaCatalog;
use LogicException;

/**
 * 《六壬大全·毕法赋》第五法「六阳数足须公用」。
 *
 * 六个阴阳检查位固定为四课四上神（sike[1/3/5/7]）与中、末传。
 * 初传只用于确认「四课中一课发用」，不重复算作第七位。这一事实口径
 * 与第62课六纯课相同，但本规则独立实现，不调用 LiuchunRule matcher。
 */
final class LiuYangShuZuRule implements BiFaRule
{
    private const BRANCH_NAMES = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];

    public function code(): string
    {
        return 'bifa.05';
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
            'description' => '四课四上神与中、末传六位全阳，或六位恰五阳一阴而占人本命、行年中有阳支填实，皆为第五法，主公用明白、利公不利私。',
            'foundations' => [
                ['code' => 'six_yang', 'title' => '六阳格', 'description' => '初传为四课上神之一，且四课四上神、中传、末传六个位置全部属阳。重复地支合法，不要求六种不同阳支齐全。'],
                ['code' => 'five_yang_filled_by_person', 'title' => '五阳年命填实', 'description' => '初传为四课上神之一，六位恰五阳一阴，且占人本命或行年的地支本身属阳。'],
            ],
            'judgments' => [
                ['label' => '公用明白·利公不利私', 'effect' => 'neutral', 'description' => '第五法成立，事主公用明白；利于公干，不利私谋。'],
                ['label' => '悖戾格', 'effect' => 'reduce', 'description' => '三传退间，又称倒拔蛇；第五法仍然成立，但事情间阻、艰辛。'],
                ['label' => '自夜传昼', 'effect' => 'enhance', 'description' => '三传由夜地传入昼方，事情尤为明白。'],
            ],
            'sections' => [
                ['title' => '六位与初传', 'content' => '六位是四课四上神加中传、末传。初传本来就是四课之一发用，须确认它在四课上神中，但不再重复计为第七个阴阳位置。'],
                ['title' => '五阳与年命填实', 'content' => '五阳指上述六位中恰有五位属阳、一位属阴。填实检查占人本命或行年的地支本身，不检查本命上神或行年上神，也不要求年命等于某个缺失的阳支。'],
                ['title' => '悖戾格与自夜传昼', 'content' => '退间传只增加悖戾格减损断义，不取消第五法，也不能单独触发本法。夜地是酉戌亥子丑寅，昼方是卯辰巳午未申；此分区与当盘昼占、夜占及所用贵人无关。'],
                ['title' => '与第六十二课六纯课的关系', 'content' => '两者采用相同的六位阴阳事实口径，但属于相互独立的毕法与课经规则体系；第五法不复用六纯课的判定器。'],
            ],
        ];
    }

    public function match(PanFacts $facts): ?BiFaRuleMatch
    {
        $sike = $facts->get('sike');
        $initial = $facts->get('sanchuan0');
        $middle = $facts->get('sanchuan1');
        $final = $facts->get('sanchuan2');
        if (! is_array($sike) || ! is_int($initial) || ! is_int($middle) || ! is_int($final)) {
            return null;
        }

        $uppers = [];
        foreach ([1, 3, 5, 7] as $index) {
            $branch = $sike[$index] ?? null;
            if (! self::isBranch($branch)) {
                return null;
            }
            $uppers[] = $branch;
        }
        if (! self::isBranch($initial) || ! self::isBranch($middle) || ! self::isBranch($final)) {
            return null;
        }

        $initialFromLesson = in_array($initial, $uppers, true);
        $positions = [...$uppers, $middle, $final];
        $yangCount = count(array_filter($positions, static fn (int $branch): bool => $branch % 2 === 0));
        $person = self::resolvePerson($facts);
        $personYang = $person !== null && (($person['nianming'] !== null && $person['nianming'] % 2 === 0)
            || ($person['xingnian'] !== null && $person['xingnian'] % 2 === 0));
        $personMissing = $person === null || ($person['nianming'] === null && $person['xingnian'] === null);
        $personPartlyMissing = $person !== null && ($person['nianming'] === null || $person['xingnian'] === null);

        $sixYang = $initialFromLesson && $yangCount === 6;
        $fiveYangCandidate = $initialFromLesson && $yangCount === 5;
        $fiveYang = $fiveYangCandidate && $personYang;
        $fiveYangPending = $fiveYangCandidate && ! $personYang && ($personMissing || $personPartlyMissing);

        $subMatches = [
            self::subMatch('six_yang', '六阳格', $sixYang, false, false,
                $sixYang ? '四课四上神与中、末传共六位皆为阳支，初传又在四课上神中。' : null),
            self::subMatch('five_yang_filled_by_person', '五阳年命填实', $fiveYang, true, $fiveYangPending,
                $fiveYang ? self::personEvidence($person) : null),
        ];
        $matchedRoutes = array_values(array_map(
            static fn (array $sub): string => $sub['code'],
            array_filter($subMatches, static fn (array $sub): bool => $sub['matched']),
        ));
        $pendingRoutes = $fiveYangPending ? ['five_yang_filled_by_person'] : [];
        if ($matchedRoutes === [] && $pendingRoutes === []) {
            return null;
        }

        $retreating = $matchedRoutes !== []
            && $middle === ($initial + 10) % 12
            && $final === ($middle + 10) % 12;
        $nightToDay = $matchedRoutes !== []
            && in_array($initial, [9, 10, 11, 0, 1, 2], true)
            && in_array($final, [3, 4, 5, 6, 7, 8], true);
        $judgments = [];
        if ($retreating) {
            $judgments[] = ['label' => '悖戾格', 'effect' => 'reduce', 'description' => '三传退间，又称倒拔蛇；第五法仍然成立，但事情间阻、艰辛。'];
        }
        if ($nightToDay) {
            $judgments[] = ['label' => '自夜传昼', 'effect' => 'enhance', 'description' => '三传由夜地传入昼方，事情尤为明白。'];
        }

        $law = $this->law();

        return new BiFaRuleMatch(
            code: $this->code(), number: $law['number'], name: $law['name'], summary: $law['summary'],
            subMatches: $subMatches, matchedRoutes: $matchedRoutes, pendingRoutes: $pendingRoutes,
            evidence: [
                'lesson_uppers' => $uppers, 'positions' => $positions, 'yang_count' => $yangCount,
                'initial_from_lesson' => $initialFromLesson, 'person' => $person,
                'retreating_interval' => $retreating, 'night_to_day' => $nightToDay,
            ],
            matchedJudgments: $judgments,
        );
    }

    private static function isBranch(mixed $value): bool
    {
        return is_int($value) && $value >= 0 && $value <= 11;
    }

    /** @return array{nianming: ?int, xingnian: ?int}|null */
    private static function resolvePerson(PanFacts $facts): ?array
    {
        $person = $facts->personByRole('querent');
        if ($person === null) {
            return null;
        }
        $nianming = self::isBranch($person['nianming'] ?? null) ? $person['nianming'] : null;
        $xingnian = self::isBranch($person['xingnian'] ?? null) ? $person['xingnian'] : null;

        return ['nianming' => $nianming, 'xingnian' => $xingnian];
    }

    private static function personEvidence(?array $person): string
    {
        $hits = [];
        if (is_int($person['nianming'] ?? null) && $person['nianming'] % 2 === 0) {
            $hits[] = '本命'.self::BRANCH_NAMES[$person['nianming']];
        }
        if (is_int($person['xingnian'] ?? null) && $person['xingnian'] % 2 === 0) {
            $hits[] = '行年'.self::BRANCH_NAMES[$person['xingnian']];
        }

        return '六个检查位恰五阳一阴，'.implode('、', $hits).'为阳支，以年命填实。';
    }

    private static function subMatch(string $code, string $title, bool $matched, bool $requiresPeople, bool $peopleMissing, ?string $detail): array
    {
        return [
            'code' => $code, 'title' => $title,
            'description' => $code === 'six_yang' ? '六个阴阳位置全部属阳。' : '六位恰五阳一阴，以占人阳支年命填实。',
            'matched' => $matched, 'detail' => $detail,
            'requires_people' => $requiresPeople, 'people_missing' => $peopleMissing,
        ];
    }
}
