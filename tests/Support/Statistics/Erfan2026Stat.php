<?php

/** 文件作用：以生产排盘、二烦规则和静态月宿表扫描2026，并统计短路阶段与真实可执行案例。 */

require dirname(__DIR__, 3).'/vendor/autoload.php';

use App\Domain\Astronomy\MoonPalaceLookup;
use App\Domain\Astronomy\MoonPalaceTable;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\ErfanRule;
use App\Services\PanCalculator;

$table = new MoonPalaceTable;
$lookup = new class($table) implements MoonPalaceLookup
{
    public int $calls = 0;

    public ?int $lastPalace = null;

    public function __construct(private MoonPalaceLookup $inner) {}

    public function palaceAt(DateTimeInterface $time): int
    {
        $this->calls++;

        return $this->lastPalace = $this->inner->palaceAt($time);
    }
};
$rule = new ErfanRule($lookup);
$calculator = new PanCalculator;
$counts = array_fill_keys([
    'total', 'four_zhong_month_general', 'four_zheng_or_four_ping', 'day_lodge_on_four_zhong',
    'dougang_on_chou_wei', 'moon_palace_queries', 'moon_lodge_on_four_zhong', 'matches',
], 0);
$matches = [];
$start = new DateTimeImmutable('2026-01-01 00:00:00', new DateTimeZone('Asia/Shanghai'));
$end = new DateTimeImmutable('2027-01-01 00:00:00', new DateTimeZone('Asia/Shanghai'));

for ($time = $start; $time < $end; $time = $time->modify('+1 hour')) {
    $facts = PanFacts::from($calculator->calculate($time->format('Y-m-d H:i:s')));
    $counts['total']++;
    $monthGeneral = $facts->get('yuejiang');
    $dayBranch = $facts->get('rizhi');
    if (! is_int($monthGeneral) || ! in_array($monthGeneral, ErfanRule::FOUR_ZHONG, true)) {
        continue;
    }
    $counts['four_zhong_month_general']++;
    $lunarDay = $facts->lunarDayNumber();
    $monthDays = $facts->lunarMonthDayCount();
    $fourZheng = is_int($lunarDay) && is_int($monthDays)
        && (in_array($lunarDay, [1, 8, 15, 23], true) || $lunarDay === $monthDays);
    $fourPing = is_int($dayBranch) && (ErfanRule::FOUR_PING[$monthGeneral] ?? null) === $dayBranch;
    if (! $fourZheng && ! $fourPing) {
        continue;
    }
    $counts['four_zheng_or_four_ping']++;
    $dayGround = $facts->heavenBranchGroundPosition($monthGeneral);
    if (! is_int($dayGround) || ! in_array($dayGround, ErfanRule::FOUR_ZHONG, true)) {
        continue;
    }
    $counts['day_lodge_on_four_zhong']++;
    $dougangGround = $facts->heavenBranchGroundPosition(4);
    if (! is_int($dougangGround) || ! in_array($dougangGround, ErfanRule::CHOU_WEI, true)) {
        continue;
    }
    $counts['dougang_on_chou_wei']++;
    $callsBefore = $lookup->calls;
    $match = $rule->match($facts);
    if ($lookup->calls !== $callsBefore + 1) {
        throw new RuntimeException('二烦前置条件通过后月宿查询次数异常。');
    }
    $counts['moon_palace_queries']++;
    $moonGround = $facts->heavenBranchGroundPosition($lookup->lastPalace);
    if (is_int($moonGround) && in_array($moonGround, ErfanRule::FOUR_ZHONG, true)) {
        $counts['moon_lodge_on_four_zhong']++;
    }
    if ($match === null) {
        continue;
    }
    $counts['matches']++;
    if (count($matches) < 30) {
        $matches[] = [
            'datetime' => $time->format('Y-m-d H:i:s'),
            'lunar_day' => $match->evidence['lunar_day'],
            'lunar_month_days' => $match->evidence['lunar_month_days'],
            'month_general' => $match->evidence['month_general'],
            'day_branch' => $match->evidence['day_branch'],
            'path' => $match->evidence['four_zheng'] ? $match->evidence['four_zheng_type'] : 'four_ping',
            'day_lodge' => $match->evidence['day_lodge'],
            'day_lodge_ground' => $match->evidence['day_lodge_ground'],
            'moon_lodge' => $match->evidence['moon_lodge'],
            'moon_lodge_ground' => $match->evidence['moon_lodge_ground'],
            'dougang_ground' => $match->evidence['dougang_ground'],
        ];
    }
}

echo json_encode([
    'range' => ['2026-01-01 00:00:00', '2027-01-01 00:00:00'],
    'timezone' => 'Asia/Shanghai',
    'sampling' => '每个民用整点，共8760盘',
    'counts' => $counts,
    'match_rate' => $counts['matches'] / $counts['total'],
    'examples' => $matches,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
