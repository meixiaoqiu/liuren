<?php

namespace App\Domain\Pan\BiFa\Rules;

use App\Domain\Pan\BiFa\BiFaRule;
use App\Domain\Pan\BiFa\BiFaRuleMatch;
use App\Domain\Pan\Facts\PanFacts;
use App\Support\BiFaCatalog;
use LogicException;

/**
 * 《六壬大全·毕法赋》第八法「权摄不正禄临支」。
 *
 * 主体路线：日干之日禄恰好等于支上神（sike[5]，即支阳课上神）。
 *
 * 十干日禄（与项目五行编号 0=木/1=火/2=土/3=金/4=水 一致，地支编号 0=子..11=亥）：
 *
 *  甲寅(2)  乙卯(3)  丙巳(5)  丁午(6)  戊巳(5)  己午(6)  庚申(8)  辛酉(9)  壬亥(11)  癸子(0)
 *
 * 本法与第七法不同：第七法仅取乙、丁、己、辛、癸五个阴干，第八法
 * 十干全部参与——日禄正临日支即可，不另看阴干阳干、不另看三传发用、
 * 不另看天将、不另看月令旺相、不另看旬空与本命行年。
 *
 * 成立后的减损判断（均为独立判定，不互斥，可重叠）：
 *
 *  - 禄受墓：日支五行 == 日禄五行之墓（十二支墓：木未/火戌/金丑/水土辰）。
 *    由于日禄五行恒为木、火、金、水四种之一，土墓戌/辰只对应火墓与水墓，
 *    而日禄本不为土支，本项实际不会出现"土墓"问题。
 *  - 禄受支克：日支五行 克 日禄五行，对应 luElement === (branchElement + 2) % 5；
 *  - 禄受支脱：日禄五行 生 日支五行，对应 branchElement === (luElement + 1) % 5。
 */
final class QuanSheBuZhengRule implements BiFaRule
{
    /** 十干日禄：key 为日干 0..9（甲0..癸9），value 为地支 0..11（子0..亥11）。 */
    private const DAY_LU = [
        0 => 2,  // 甲禄寅
        1 => 3,  // 乙禄卯
        2 => 5,  // 丙禄巳
        3 => 6,  // 丁禄午
        4 => 5,  // 戊禄巳
        5 => 6,  // 己禄午
        6 => 8,  // 庚禄申
        7 => 9,  // 辛禄酉
        8 => 11, // 壬禄亥
        9 => 0,  // 癸禄子
    ];

    /** 十二地支五行之墓：木未=7、火戌=10、金丑=1、水土辰=4。 */
    private const LU_GRAVE = [
        0 => 7,  // 木墓未
        1 => 10, // 火墓戌
        3 => 1,  // 金墓丑
        4 => 4,  // 水墓辰
    ];

    public function code(): string
    {
        return 'bifa.08';
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
            'description' => '日干之禄神正临日支之上，即为"禄临支"。表示本属于自身的禄位、职位或可支配资源落于支方，行事容易受他方牵制或借他方而得禄；具体吉凶仍须结合全盘判断。',
            'foundations' => [[
                'code' => 'lu_on_branch',
                'title' => '日禄临支',
                'description' => '日干之禄神恰好等于支阳课上神（项目四课数据 sike[5]，即"支上神"）。十干全部参与，不分阴阳。',
            ]],
            'judgments' => [
                ['label' => '禄受墓', 'effect' => 'reduce', 'description' => '日支为日禄五行之墓者：日禄归墓于支方，禄气被收入墓库，自身之禄进一步被支方收纳。'],
                ['label' => '禄受支克', 'effect' => 'reduce', 'description' => '日支五行克日禄五行者：支方主导禄神，自身禄位受制于支方，行事容易受他方牵制。'],
                ['label' => '禄受支脱', 'effect' => 'reduce', 'description' => '日禄五行生日支五行者：禄气泄于支方，可支配资源流向他方，本身看似得禄而实则消耗。'],
            ],
            'sections' => [
                ['title' => '主体唯一路线', 'content' => '本法仅设"日禄临支"一条成立路线：日干之日禄恰等于支阳课上神（项目四课数据 sike[5]，即古籍所谓"支上神"）。三传、日干上神、占时、天将、月令、本命、行年均不参与主体成立判断。'],
                ['title' => '十干全部参与', 'content' => '与第七法只取乙、丁、己、辛、癸五阴干不同，第八法取完整十干日禄——甲禄寅、乙禄卯、丙戊禄巳、丁己禄午、庚禄申、辛禄酉、壬禄亥、癸禄子。'],
                ['title' => '三项减损可重叠', 'content' => '墓、支克、支脱三者各自独立判定，互不互斥。一盘可能同时命中一项以上，例如金禄临火支既符合"火克金"又可能符合"金生水"以外的支生关系，具体由支支五行严格比较得出。'],
                ['title' => '断义不绝对吉凶', 'content' => '"禄临支"本身并不必然主失官或必然主得禄，仅表示禄寄于支方；具体吉凶须结合三传、天将、月令与人事。程序不写"必然失禄""必凶"等绝对断义。'],
            ],
        ];
    }

    public function match(PanFacts $facts): ?BiFaRuleMatch
    {
        $rigan = $facts->get('rigan');
        $rizhi = $facts->get('rizhi');
        $sike = $facts->get('sike');

        if (! is_int($rigan) || ! array_key_exists($rigan, self::DAY_LU)) {
            return null;
        }
        if (! is_int($rizhi) || $rizhi < 0 || $rizhi > 11) {
            return null;
        }
        if (! is_array($sike) || ! array_key_exists(5, $sike)
            || ! is_int($sike[5]) || $sike[5] < 0 || $sike[5] > 11) {
            return null;
        }

        $dayLu = self::DAY_LU[$rigan];
        $branchUpper = $sike[5];
        if ($branchUpper !== $dayLu) {
            return null;
        }

        $luElement = $facts->branchElement($dayLu);
        $branchElement = $facts->branchElement($rizhi);
        $luGrave = $luElement === null ? null : (self::LU_GRAVE[$luElement] ?? null);

        $luTombed = $luGrave !== null && $branchElement !== null && $rizhi === $luGrave;
        $luControlled = $luElement !== null && $branchElement !== null
            && $luElement === ($branchElement + 2) % 5;
        $luDrained = $luElement !== null && $branchElement !== null
            && $branchElement === ($luElement + 1) % 5;

        $judgments = [];
        if ($luTombed) {
            $judgments[] = ['label' => '禄受墓', 'effect' => 'reduce', 'description' => '日支为日禄五行之墓：日禄归墓于支方，禄气被收纳。'];
        }
        if ($luControlled) {
            $judgments[] = ['label' => '禄受支克', 'effect' => 'reduce', 'description' => '日支五行克日禄五行：支方主导禄神，自身禄位受制。'];
        }
        if ($luDrained) {
            $judgments[] = ['label' => '禄受支脱', 'effect' => 'reduce', 'description' => '日禄五行生日支五行：禄气泄于支方，本身消耗。'];
        }

        $law = $this->law();

        return new BiFaRuleMatch(
            code: $this->code(), number: $law['number'], name: $law['name'], summary: $law['summary'],
            subMatches: [[
                'code' => 'lu_on_branch', 'title' => '日禄临支',
                'description' => '日干之禄神等于支阳课上神（支上神）。',
                'matched' => true,
                'detail' => '日干之日禄等于支上神。',
                'requires_people' => false, 'people_missing' => false,
            ]],
            matchedRoutes: ['lu_on_branch'],
            evidence: [
                'day_lu' => $dayLu,
                'branch_upper' => $branchUpper,
                'day_branch' => $rizhi,
                'lu_element' => $luElement,
                'branch_element' => $branchElement,
                'lu_grave' => $luGrave,
                'lu_tombed_by_branch' => $luTombed,
                'lu_controlled_by_branch' => $luControlled,
                'lu_drained_by_branch' => $luDrained,
            ],
            matchedJudgments: $judgments,
        );
    }
}
