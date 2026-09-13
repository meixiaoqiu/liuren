<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Astronomy\MoonPalaceLookup;
use App\Domain\Astronomy\MoonPalaceTable;
use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;
use DateTimeImmutable;
use DateTimeZone;

/** 文件作用：按《大全》四仲月将、四正或四平、天地二烦并见的主体定义判断二烦课。 */
final class ErfanRule implements PanRule
{
    /** @var list<int> */
    public const FOUR_ZHONG = [0, 3, 6, 9];

    /** @var list<int> */
    public const CHOU_WEI = [1, 7];

    /** @var array<int, int> */
    public const FOUR_PING = [0 => 3, 3 => 6, 6 => 9, 9 => 0];

    public function __construct(private MoonPalaceLookup $moonPalace = new MoonPalaceTable) {}

    public function code(): string
    {
        return 'lesson.erfan';
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $monthGeneral = $facts->get('yuejiang');
        $dayBranch = $facts->get('rizhi');
        $calculationTime = $facts->get('calculationTime');
        $tianpan = $facts->get('tianpan');
        if (! is_int($monthGeneral) || ! is_int($dayBranch) || ! is_string($calculationTime) || ! is_array($tianpan)) {
            return null;
        }

        $fourZhongMonthGeneral = in_array($monthGeneral, self::FOUR_ZHONG, true);
        if (! $fourZhongMonthGeneral) {
            return null;
        }

        $lunarDay = $facts->lunarDayNumber();
        $lunarMonthDays = $facts->lunarMonthDayCount();
        if ($lunarDay === null || $lunarMonthDays === null) {
            return null;
        }
        $fourZhengType = match ($lunarDay) {
            1 => 'new_moon_day', 8 => 'first_quarter_day', 15 => 'full_moon_day', 23 => 'last_quarter_day',
            default => $lunarDay === $lunarMonthDays ? 'month_end' : null,
        };
        $fourZheng = $fourZhengType !== null;
        $fourPingExpectedBranch = self::FOUR_PING[$monthGeneral];
        $fourPing = $dayBranch === $fourPingExpectedBranch;
        if (! $fourZheng && ! $fourPing) {
            return null;
        }

        $dayLodge = $monthGeneral;
        $dayLodgeGround = $facts->heavenBranchGroundPosition($dayLodge);
        $dayLodgeOnFourZhong = is_int($dayLodgeGround) && in_array($dayLodgeGround, self::FOUR_ZHONG, true);
        if (! $dayLodgeOnFourZhong) {
            return null;
        }

        $dougang = 4;
        $dougangGround = $facts->heavenBranchGroundPosition($dougang);
        $dougangOnChouWei = is_int($dougangGround) && in_array($dougangGround, self::CHOU_WEI, true);
        if (! $dougangOnChouWei) {
            return null;
        }

        $time = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $calculationTime, new DateTimeZone('Asia/Shanghai'));
        $errors = DateTimeImmutable::getLastErrors();
        if ($time === false || (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return null;
        }

        $moonLodge = $this->moonPalace->palaceAt($time);
        $moonLodgeGround = $facts->heavenBranchGroundPosition($moonLodge);
        $moonLodgeOnFourZhong = is_int($moonLodgeGround) && in_array($moonLodgeGround, self::FOUR_ZHONG, true);
        $tianfan = $dayLodgeOnFourZhong && $dougangOnChouWei;
        $difan = $moonLodgeOnFourZhong && $dougangOnChouWei;
        if (! $difan) {
            return null;
        }

        $branch = fn (int $value): string => PanCalculator::$dizhi[$value] ?? '?';
        $fourZhengLabel = match ($fourZhengType) {
            'new_moon_day' => '朔（初一）', 'first_quarter_day' => '上弦日（初八）',
            'full_moon_day' => '望（十五）', 'last_quarter_day' => '下弦日（二十三）',
            'month_end' => '晦（月终）', default => '不成立',
        };

        return new RuleMatch(
            code: $this->code(), name: '二烦课', group: '六十四课',
            description: '四仲月将，逢四正或四平日，日月宿俱临四仲，斗罡系丑未。',
            gua: '明夷', guaSymbol: '䷣',
            xiang: '男遇天烦，命遭刑戮。女犯地烦，身受蛊毒。征战伤亡，疾病嚎哭。狱讼徒流，胎孕不育。',
            evidence: [
                'month_general' => $monthGeneral, 'four_zhong_month_general' => $fourZhongMonthGeneral,
                'lunar_day' => $lunarDay, 'lunar_month_days' => $lunarMonthDays,
                'four_zheng' => $fourZheng, 'four_zheng_type' => $fourZhengType,
                'day_branch' => $dayBranch, 'four_ping' => $fourPing,
                'four_ping_expected_branch' => $fourPingExpectedBranch,
                'day_lodge' => $dayLodge, 'day_lodge_ground' => $dayLodgeGround,
                'day_lodge_on_four_zhong' => $dayLodgeOnFourZhong,
                'moon_lodge' => $moonLodge, 'moon_lodge_ground' => $moonLodgeGround,
                'moon_lodge_on_four_zhong' => $moonLodgeOnFourZhong,
                'moon_palace_source' => 'static_boundary_table',
                'moon_palace_model' => 'astronomy-engine-2.1.19',
                'dougang' => $dougang, 'dougang_ground' => $dougangGround,
                'dougang_on_chou_wei' => $dougangOnChouWei,
                'tianfan' => $tianfan, 'difan' => $difan,
                'foundations' => [
                    ['title' => '四仲月将', 'detail' => '月将'.$branch($monthGeneral).'属于子卯午酉四仲。'],
                    ['title' => '四正 / 四平', 'detail' => $fourZheng ? "农历{$lunarDay}日为{$fourZhengLabel}。" : '月将'.$branch($monthGeneral).'所平日支为'.$branch($fourPingExpectedBranch).'，本日相符。'],
                    ['title' => '日宿临仲', 'detail' => '日宿即月将'.$branch($dayLodge).'，加临地盘'.$branch($dayLodgeGround).'，属于四仲。'],
                    ['title' => '月宿临仲', 'detail' => '静态交宫表所得月宿'.$branch($moonLodge).'，加临地盘'.$branch($moonLodgeGround).'，属于四仲。'],
                    ['title' => '斗罡系丑未', 'detail' => '斗罡辰加临地盘'.$branch($dougangGround).'，属于丑未。'],
                    ['title' => '天地二烦', 'detail' => '日宿临仲成天烦，月宿临仲成地烦，天地二烦同时成立。'],
                ],
                'judgments' => [],
                'uncovered' => ['《订讹》“四仲月日及四正日占之更的”为后世增强条件，未加入主体。', '《订讹》“日月宿不发用者不真”为真格增强条件，未加入主体。'],
            ],
        );
    }
}
