<?php

/** 文件作用：以720核心盘输入统计二烦课各级短路条件与静态月宿查询次数。 */

require dirname(__DIR__, 3).'/vendor/autoload.php';

use App\Domain\Astronomy\MoonPalaceLookup;
use App\Domain\Astronomy\MoonPalaceTable;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\Rules\ErfanRule;
use App\Services\PanCalculator;

$fixture = json_decode(file_get_contents(dirname(__DIR__, 2).'/Fixtures/pan_regression_720.json'), true, flags: JSON_THROW_ON_ERROR);
$inputs = array_column($fixture['cases'], 'input');
$lookup = new class(new MoonPalaceTable) implements MoonPalaceLookup
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
$counts = array_fill_keys(['total', 'four_zhong_month_general', 'four_zheng_or_four_ping', 'day_lodge_on_four_zhong', 'dougang_on_chou_wei', 'moon_palace_queries', 'moon_lodge_on_four_zhong', 'matches'], 0);

foreach ($inputs as $datetime) {
    $facts = PanFacts::from($calculator->calculate($datetime));
    $counts['total']++;
    $monthGeneral = $facts->get('yuejiang');
    $dayBranch = $facts->get('rizhi');
    if (! is_int($monthGeneral) || ! in_array($monthGeneral, ErfanRule::FOUR_ZHONG, true)) {
        continue;
    }
    $counts['four_zhong_month_general']++;
    $day = $facts->lunarDayNumber();
    $monthDays = $facts->lunarMonthDayCount();
    $fourZheng = is_int($day) && is_int($monthDays) && (in_array($day, [1, 8, 15, 23], true) || $day === $monthDays);
    if (! $fourZheng && (ErfanRule::FOUR_PING[$monthGeneral] ?? null) !== $dayBranch) {
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
    $before = $lookup->calls;
    $match = $rule->match($facts);
    if ($lookup->calls !== $before + 1) {
        throw new RuntimeException("月宿查询短路计数异常：{$datetime}");
    }
    $counts['moon_palace_queries']++;
    $moonGround = $facts->heavenBranchGroundPosition($lookup->lastPalace);
    if (is_int($moonGround) && in_array($moonGround, ErfanRule::FOUR_ZHONG, true)) {
        $counts['moon_lodge_on_four_zhong']++;
    }
    $counts['matches'] += (int) ($match !== null);
}

echo json_encode([
    'source' => 'tests/Fixtures/pan_regression_720.json 的720个生产输入',
    'counts' => $counts,
    'match_rate_percent' => round($counts['matches'] * 100 / $counts['total'], 6),
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
