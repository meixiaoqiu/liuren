<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Astronomy\MoonPalaceLookup;
use App\Domain\Astronomy\MoonPalaceTable;
use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;
use DateTimeImmutable;
use DateTimeZone;
use OutOfRangeException;

/** 文件作用：按“分至日 AND 月宿临前一日支（离辰）”冻结口径判断天寇课；发用不参与基础成立条件。 */
final class TiankouRule implements ConditionalEvaluationRule, PanRule
{
    use LessonDefinitionDefaults;

    public function __construct(private MoonPalaceLookup $moonPalace = new MoonPalaceTable) {}

    public function code(): string
    {
        return 'lesson.tiankou';
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $fenZhi = $facts->fenZhiDay();
        $dayIndex = $facts->civilDaySexagenaryDayIndex();
        $calculationTime = $facts->get('calculationTime');
        $tianpan = $facts->get('tianpan');

        if ($fenZhi === null || $dayIndex === null || ! is_string($calculationTime) || ! is_array($tianpan)) {
            return null;
        }

        $dayStem = $dayIndex % 10;
        $dayBranch = $dayIndex % 12;
        $previousDayIndex = ($dayIndex + 59) % 60;
        $previousDayStem = $previousDayIndex % 10;
        $previousDayBranch = $previousDayIndex % 12;
        $time = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $calculationTime, new DateTimeZone('+08:00'));
        $errors = DateTimeImmutable::getLastErrors();
        if ($time === false || (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return null;
        }

        try {
            $moonPalace = $this->moonPalace->palaceAt($time);
        } catch (OutOfRangeException) {
            return null;
        }

        $moonPalaceGround = $facts->heavenBranchGroundPosition($moonPalace);
        $moonPalaceOnLiBranch = $moonPalaceGround === $previousDayBranch;
        if (! $moonPalaceOnLiBranch) {
            return null;
        }

        $stem = fn (int $value): string => PanCalculator::$tiangan[$value] ?? '?';
        $branch = fn (int $value): string => PanCalculator::$dizhi[$value] ?? '?';
        $day = $stem($dayStem).$branch($dayBranch);
        $previousDay = $stem($previousDayStem).$branch($previousDayBranch);

        return new RuleMatch(
            code: $this->code(), name: '天寇课', group: '六十四课',
            description: '分至日，月宿加临前一日地支（离辰）。',
            gua: '蹇', guaSymbol: '䷦',
            xiang: '阴阳分离，气不得反。盗贼滋生，军兵堕懒。病者即亡，孕妇当产。出路死伤，婚姻拆散。',
            evidence: [
                'fen_zhi' => true,
                'fen_zhi_key' => $fenZhi['key'],
                'fen_zhi_name' => $fenZhi['name'],
                'fen_zhi_term_time' => $fenZhi['term_time'],
                'day_index' => $dayIndex,
                'day_stem' => $dayStem,
                'day_branch' => $dayBranch,
                'day' => $day,
                'previous_day_index' => $previousDayIndex,
                'previous_day_stem' => $previousDayStem,
                'previous_day_branch' => $previousDayBranch,
                'previous_day' => $previousDay,
                'li_branch' => $previousDayBranch,
                'moon_palace' => $moonPalace,
                'moon_palace_ground' => $moonPalaceGround,
                'moon_palace_on_li_branch' => $moonPalaceOnLiBranch,
                'moon_palace_source' => 'static_boundary_table',
                'moon_palace_model' => 'astronomy-engine-2.1.19',
                'foundations' => [
                    ['title' => '分至日', 'detail' => "本日为{$fenZhi['name']}日，{$fenZhi['name']}精确交节时间为{$fenZhi['term_time']}；按交节所在整个公历日期判断。"],
                    ['title' => '前一日支为离辰', 'detail' => "本日{$day}，前一日{$previousDay}，故离辰为{$branch($previousDayBranch)}。"],
                    ['title' => '月宿临离辰', 'detail' => '静态天文交宫表所得当前月宿为'.$branch($moonPalace).'；天盘月宿'.$branch($moonPalace).'加临地盘'.$branch($moonPalaceGround).'，与离辰同为'.$branch($previousDayBranch).'。'],
                ],
                'judgments' => [],
                'uncovered' => [
                    '月宿发用、入三传与否只作凶应增强，不是基础成立条件。',
                    '玄武、勾陈、白虎、游都、盗神、日鬼、劫煞、真天寇、年命、日月并明与败寇等均未加入基础判断。',
                ],
            ],
        );
    }

    public function evaluationIssue(PanFacts $facts): ?array
    {
        if ($facts->fenZhiDay() === null) {
            return null;
        }

        $calculationTime = $facts->get('calculationTime');
        if (! is_string($calculationTime)) {
            return null;
        }

        $time = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $calculationTime, new DateTimeZone('+08:00'));
        $errors = DateTimeImmutable::getLastErrors();
        if ($time === false || (is_array($errors) && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            return null;
        }

        try {
            $this->moonPalace->palaceAt($time);
        } catch (OutOfRangeException $exception) {
            return [
                'name' => '天寇课',
                'notice' => '月宿交宫表超出支持范围，天寇课未进行判断。'.$exception->getMessage(),
            ];
        }

        return null;
    }
}
