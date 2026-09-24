<?php

namespace App\Domain\Pan\BiFa\Rules;

use App\Domain\Pan\BiFa\BiFaRule;
use App\Domain\Pan\BiFa\BiFaRuleMatch;
use App\Domain\Pan\Facts\PanFacts;
use App\Support\BiFaCatalog;
use LogicException;

/** 《毕法赋》第三法：八项分格各自独立，断义与减损不参与成立判断。 */
final class LianMuGuiRenRule implements BiFaRule
{
    private const BRANCH_NAMES = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];

    private const DAY_NOBLE = [1, 0, 11, 11, 1, 0, 1, 6, 5, 5];

    private const NIGHT_NOBLE = [7, 8, 9, 9, 7, 8, 7, 2, 3, 3];

    private const DAY_VIRTUES = [2, 8, 5, 11, 5, 2, 8, 5, 11, 5];

    public function code(): string
    {
        return 'bifa.03';
    }

    public function law(): array
    {
        return BiFaCatalog::findByCode($this->code())
            ?? throw new LogicException('BiFaCatalog 找不到 '.$this->code().'；注册表与目录脱节。');
    }

    public function definition(): array
    {
        return [
            'description' => '昼占取夜贵、夜占取昼贵为帘幕贵人；帘幕临干年命、旬首帘幕、辰戌旬首、斗鬼、亚魁、德入天门、真朱雀或昼夜二贵拱年命，皆为第三法科名之象。',
            'foundations' => [
                ['code' => 'curtain_noble_on_stem_or_fate', 'title' => '帘幕贵人临干年命', 'description' => '帘幕贵人加临日干寄宫、本命宫或行年宫。'],
                ['code' => 'xun_head_as_curtain_noble', 'title' => '旬首作帘幕', 'description' => '旬首恰为帘幕贵人，且加临日干寄宫、本命宫或行年宫。'],
                ['code' => 'chen_xu_xun_head_on_stem_or_fate', 'title' => '辰戌旬首临干年命', 'description' => '辰或戌作旬首，并加临日干寄宫、本命宫或行年宫。'],
                ['code' => 'dou_gui_on_stem_or_fate', 'title' => '斗鬼相加', 'description' => '丑加未或未加丑，发生在日干寄宫、本命宫或行年宫。'],
                ['code' => 'ya_kui_you_on_stem_or_fate', 'title' => '亚魁临干年命', 'description' => '酉加临日干寄宫、本命宫或行年宫。'],
                ['code' => 'day_virtue_enters_heaven_gate', 'title' => '德入天门', 'description' => '日德加临地盘亥宫，并以日德发用。'],
                ['code' => 'true_vermilion_bird', 'title' => '真朱雀', 'description' => '己日、四季年、夜贵逆布，并且朱雀乘午。'],
                ['code' => 'two_nobles_flank_fate', 'title' => '昼夜二贵拱年命', 'description' => '昼夜二贵分别临干支，且日干寄宫与日支夹拱本命或行年。'],
            ],
            'judgments' => [
                ['label' => '前五类尤忌旬空', 'effect' => 'reduce', 'description' => '上述前五类科名结构所用关键神落本旬空亡，则力量受损；正文尤忌空亡。'],
                ['label' => '帘幕空墓受克减力', 'effect' => 'reduce', 'description' => '帘幕贵人逢空亡、入墓或受克，科名之力减损；逐日喜忌详见研究记录。'],
                ['label' => '朱雀克帘幕减力', 'effect' => 'reduce', 'description' => '朱雀所乘地支五行克帘幕贵人五行，正文主文章不合主文之意。'],
            ],
            'sections' => [
                ['title' => '帘幕贵人的昼夜取法', 'content' => '帘幕不是当前所用天乙贵人：昼占反取夜贵，夜占反取昼贵。'],
                ['title' => '人物资料与待评估规则', 'content' => '前五类先判断不依赖人物的日干路径；日干已命中即成立，否则在本命、行年均缺时待评估。昼夜二贵拱年命无人物资料时必待评估。'],
                ['title' => '空亡作用范围', 'content' => '“以上诸说忌空亡”只覆盖帘幕、旬首帘幕、辰戌旬首、斗鬼与亚魁，不扩展到后三类。'],
                ['title' => '不采用的后世扩展', 'content' => '武举法、文昌、青龙、朱雀旺相、考试占事类型不作主体入口；朱雀克帘幕、空墓受克与榜将出只作减损或未覆盖说明。'],
            ],
        ];
    }

    public function match(PanFacts $facts): ?BiFaRuleMatch
    {
        $tianpan = $facts->get('tianpan');
        $rigan = $facts->get('rigan');
        $rizhi = $facts->get('rizhi');
        $nianzhi = $facts->get('nianzhi');
        $period = $facts->get('guirenPeriod');
        $initial = $facts->get('sanchuan0');
        if (! is_array($tianpan) || ! is_int($rigan) || ! is_int($rizhi) || ! is_int($nianzhi)
            || ! in_array($period, ['day', 'night'], true) || ! is_int($initial)) {
            return null;
        }
        $lodging = $facts->stemLodgingBranch($rigan);
        $xunHead = $facts->dayXunHeadBranch();
        if ($lodging === null || $xunHead === null) {
            return null;
        }
        $curtain = $period === 'day' ? (self::NIGHT_NOBLE[$rigan] ?? null) : (self::DAY_NOBLE[$rigan] ?? null);
        $dayNoble = self::DAY_NOBLE[$rigan] ?? null;
        $nightNoble = self::NIGHT_NOBLE[$rigan] ?? null;
        if ($curtain === null || $dayNoble === null || $nightNoble === null) {
            return null;
        }
        $person = self::person($facts);
        $peopleMissing = $person === null;
        $grounds = ['日干寄宫' => $lodging];
        if ($person !== null) {
            if ($person['nianming'] !== null) {
                $grounds['本命宫'] = $person['nianming'];
            } if ($person['xingnian'] !== null) {
                $grounds['行年宫'] = $person['xingnian'];
            }
        }
        $stemCurtain = ($tianpan[$lodging] ?? null) === $curtain;
        $curtainHit = self::upperHits($tianpan, $grounds, $curtain);
        $xunStem = ($tianpan[$lodging] ?? null) === $xunHead;
        $xunHit = self::upperHits($tianpan, $grounds, $xunHead);
        $douHit = self::douHits($tianpan, $grounds);
        $yaKuiHit = self::upperHits($tianpan, $grounds, 9);
        $twoNobles = self::samePair($tianpan[$lodging] ?? null, $tianpan[$rizhi] ?? null, $dayNoble, $nightNoble);
        $flankHit = $person !== null && self::flanksPerson($lodging, $rizhi, $person);
        $routes = [
            ['curtain_noble_on_stem_or_fate', '帘幕贵人临干年命', $curtainHit, $peopleMissing && ! $stemCurtain, '帘幕贵人加临日干寄宫、本命宫或行年宫。'],
            ['xun_head_as_curtain_noble', '旬首作帘幕', $xunHead === $curtain && $xunHit, $peopleMissing && $xunHead === $curtain && ! $xunStem, '旬首恰为帘幕贵人，并加临日干寄宫、本命宫或行年宫。'],
            ['chen_xu_xun_head_on_stem_or_fate', '辰戌旬首临干年命', in_array($xunHead, [4, 10], true) && $xunHit, $peopleMissing && in_array($xunHead, [4, 10], true) && ! $xunStem, '辰或戌作旬首，并加临日干寄宫、本命宫或行年宫。'],
            ['dou_gui_on_stem_or_fate', '斗鬼相加', $douHit, $peopleMissing && ! self::douAt($tianpan, $lodging), '丑加未或未加丑，发生在日干寄宫、本命宫或行年宫。'],
            ['ya_kui_you_on_stem_or_fate', '亚魁临干年命', $yaKuiHit, $peopleMissing && (($tianpan[$lodging] ?? null) !== 9), '酉加临日干寄宫、本命宫或行年宫。'],
            ['day_virtue_enters_heaven_gate', '德入天门', ($tianpan[11] ?? null) === self::DAY_VIRTUES[$rigan] && $initial === self::DAY_VIRTUES[$rigan], false, '日德加临地盘亥宫，并以日德发用。'],
            ['true_vermilion_bird', '真朱雀', $rigan === 5 && in_array($nianzhi, [4, 10, 1, 7], true) && $period === 'night' && $facts->isNoblemanMovingBackward() && $facts->generalRidingBranch(6) === 2, false, '己日、四季年、夜贵逆布，并且午乘朱雀。'],
            ['two_nobles_flank_fate', '昼夜二贵拱年命', $twoNobles && $flankHit, $peopleMissing, '昼夜二贵分别临干支，且干支夹拱本命或行年。'],
        ];
        $sub = [];
        $matched = [];
        $pending = [];
        foreach ($routes as [$code,$title,$hit,$missing,$description]) {
            $sub[] = ['code' => $code, 'title' => $title, 'description' => $description, 'matched' => $hit, 'detail' => $hit ? $description : null, 'requires_people' => $missing, 'people_missing' => $missing];
            if ($hit) {
                $matched[] = $code;
            } elseif ($missing) {
                $pending[] = $code;
            }
        }
        if ($matched === [] && $pending === []) {
            return null;
        }

        return new BiFaRuleMatch($this->code(), $this->law()['number'], $this->law()['name'], $this->law()['summary'], $sub, $matched, $pending, [
            'rigan' => $rigan, 'rizhi' => $rizhi, 'guiren_period' => $period, 'curtain_noble' => $curtain, 'xun_head' => $xunHead,
            'curtain_is_void' => $facts->isBranchXunVoid($curtain), 'xun_head_is_void' => $facts->isBranchXunVoid($xunHead),
            'day_noble' => $dayNoble, 'night_noble' => $nightNoble, 'day_virtue' => self::DAY_VIRTUES[$rigan],
        ]);
    }

    private static function upperHits(array $tianpan, array $grounds, int $target): bool
    {
        foreach ($grounds as $g) {
            if (($tianpan[$g] ?? null) === $target) {
                return true;
            }
        }

return false;
    }

    private static function douAt(array $tianpan, int $g): bool
    {
        return ($g === 7 && ($tianpan[$g] ?? null) === 1) || ($g === 1 && ($tianpan[$g] ?? null) === 7);
    }

    private static function douHits(array $tianpan, array $grounds): bool
    {
        foreach ($grounds as $g) {
            if (self::douAt($tianpan, $g)) {
                return true;
            }
        }

return false;
    }

    private static function samePair(mixed $a, mixed $b, int $x, int $y): bool
    {
        return is_int($a) && is_int($b) && (($a === $x && $b === $y) || ($a === $y && $b === $x));
    }

    private static function flanks(int $a, int $b, int $t): bool
    {
        return ($a === ($t + 1) % 12 && $b === ($t + 11) % 12) || ($b === ($t + 1) % 12 && $a === ($t + 11) % 12);
    }

    private static function flanksPerson(int $a, int $b, array $p): bool
    {
        return ($p['nianming'] !== null && self::flanks($a, $b, $p['nianming'])) || ($p['xingnian'] !== null && self::flanks($a, $b, $p['xingnian']));
    }

    private static function person(PanFacts $facts): ?array
    {
        $p = $facts->personByRole('querent');
        if ($p === null) {
            return null;
        } $n = is_int($p['nianming'] ?? null) ? $p['nianming'] : null;
        $x = is_int($p['xingnian'] ?? null) ? $p['xingnian'] : null;

        return $n === null && $x === null ? null : ['nianming' => $n, 'xingnian' => $x];
    }
}
