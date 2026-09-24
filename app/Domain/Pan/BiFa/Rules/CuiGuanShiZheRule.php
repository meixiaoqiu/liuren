<?php

namespace App\Domain\Pan\BiFa\Rules;

use App\Domain\Pan\BiFa\BiFaRule;
use App\Domain\Pan\BiFa\BiFaRuleMatch;
use App\Domain\Pan\BranchRelations;
use App\Domain\Pan\Facts\PanFacts;
use App\Support\BiFaCatalog;
use LogicException;

/**
 * 文件作用：按《毕法赋》第四法"催官使者赴官期"逐分格判断当前盘面。
 *
 * 第四法共整理为 4 条正式 matcher route，任一成立即第四法成立：
 *
 *  - 1. 催官使者                  → route cui_guan_messenger
 *      日鬼/官星乘白虎（天将序号 7）加临日干寄宫、本命或行年。
 *
 *  - 2. 催官符                    → route cui_guan_talisman
 *      官星加临干支/本命/行年，且三传组成完整三合局，局五行生官星五行。
 *
 *  - 3. 恩主举荐·父母爻          → route patron_parent_line
 *      父母爻出现在干上神、支上神、初传、中传、末传、本命上神或行年上神任一处。
 *
 *  - 4. 恩主举荐·长生作贵人      → route patron_noble_as_growth
 *      当前所用天乙贵人地支等于日干六壬五行长生支。
 *
 * 日鬼/官星表（普通五行官鬼定义，绝不复用 GuimuRule::DAY_GHOSTS 特殊表）：
 *
 *  - 甲乙(木) → 申、酉（金）
 *  - 丙丁(火) → 亥、子（水）
 *  - 戊己(土) → 寅、卯（木）
 *  - 庚辛(金) → 巳、午（火）
 *  - 壬癸(水) → 辰、戌、丑、未（土）
 *
 * 父母爻表：
 *
 *  - 甲乙木日 → 亥、子（水）
 *  - 丙丁火日 → 寅、卯（木）
 *  - 戊己土日 → 巳、午（火）
 *  - 庚辛金日 → 辰、戌、丑、未（土）
 *  - 壬癸水日 → 申、酉（金）
 *
 * 长生位（与 TianyuRule::DAY_ORIGIN 一致）：
 *
 *  - 甲乙 → 亥（11）
 *  - 丙丁 → 寅（2）
 *  - 戊己 → 申（8）
 *  - 庚辛 → 巳（5）
 *  - 壬癸 → 申（8）
 *
 * 三合局五行映射：
 *
 *  - 申子辰 = 水
 *  - 亥卯未 = 木
 *  - 寅午戌 = 火
 *  - 巳酉丑 = 金
 *
 * judgments：
 *
 *  - 催官使者空亡       reduce
 *  - 四时返本煞         reduce（春金、夏水、秋火、冬寅午戌）
 *  - 返吟附加判断       reduce
 *
 * 返本煞绝不参与成立判断；返吟绝不单独触发第四法；催官使者空亡不阻塞 route，
 * 只在已成立后作为减损断义。
 */
final class CuiGuanShiZheRule implements BiFaRule
{
    private const BRANCH_NAMES = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];

    private const ELEMENT_NAMES = ['木', '火', '土', '金', '水'];

    /** @var array<int, int> 十干昼贵：甲戊庚丑、乙己子、丙丁亥、六辛午、壬癸巳 */
    private const DAY_NOBLE = [1, 0, 11, 11, 1, 0, 1, 6, 5, 5];

    /** @var array<int, int> 十干夜贵：甲戊庚未、乙己申、丙丁酉、六辛寅、壬癸卯 */
    private const NIGHT_NOBLE = [7, 8, 9, 9, 7, 8, 7, 2, 3, 3];

    /**
     * 普通五行官鬼（与课经 GuimuRule::DAY_GHOSTS 同性单鬼表不同）。
     *
     *  - 甲乙(木) → 金（申、酉）
     *  - 丙丁(火) → 水（亥、子）
     *  - 戊己(土) → 木（寅、卯）
     *  - 庚辛(金) → 火（巳、午）
     *  - 壬癸(水) → 土（辰、戌、丑、未）
     *
     * @var array<int, list<int>>
     */
    private const DAY_OFFICIALS = [
        0 => [8, 9],
        1 => [8, 9],
        2 => [0, 11],
        3 => [0, 11],
        4 => [2, 3],
        5 => [2, 3],
        6 => [5, 6],
        7 => [5, 6],
        8 => [4, 10, 1, 7],
        9 => [4, 10, 1, 7],
    ];

    /**
     * 父母爻表。
     *
     *  - 甲乙木日 → 亥、子（水）
     *  - 丙丁火日 → 寅、卯（木）
     *  - 戊己土日 → 巳、午（火）
     *  - 庚辛金日 → 辰、戌、丑、未（土）
     *  - 壬癸水日 → 申、酉（金）
     *
     * @var array<int, list<int>>
     */
    private const PARENT_LINES = [
        0 => [0, 11],
        1 => [0, 11],
        2 => [2, 3],
        3 => [2, 3],
        4 => [5, 6],
        5 => [5, 6],
        6 => [1, 4, 7, 10],
        7 => [1, 4, 7, 10],
        8 => [8, 9],
        9 => [8, 9],
    ];

    /**
     * 六壬五行长生位。与 TianyuRule::DAY_ORIGIN 完全一致；本法独立保存一份以
     * 避免跨体系耦合，原表有变化时此处必须同步。
     *
     * @var array<int, int>
     */
    private const DAY_ORIGIN = [11, 11, 2, 2, 8, 8, 5, 5, 8, 8];

    /**
     * 标准化三合局 → 五行映射。
     *
     *  - 申子辰水 → 4
     *  - 亥卯未木 → 0
     *  - 寅午戌火 → 1
     *  - 巳酉丑金 → 3
     *
     * @var array<string, int>
     */
    private const SANHE_ELEMENT_MAP = [
        '0,4,8' => 4,
        '1,5,9' => 3,
        '2,6,10' => 1,
        '3,7,11' => 0,
    ];

    /** 五行相生：木→火→土→金→水→木。 */
    private const ELEMENT_SHENG = [1, 2, 3, 4, 0];

    /** 白虎序号：现有天将序列中 0=贵,1=蛇,2=雀,3=合,4=勾,5=龙,6=空,7=虎,8=常,9=武,10=阴,11=后。 */
    private const GENERAL_BAIHU = 7;

    public function code(): string
    {
        return 'bifa.04';
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
            'description' => '日鬼或官星乘白虎加临干支年命为催官使者；或官星临干年命而三传组成三合局、局生官星为催官符；或父母爻见于日辰三传年命，或长生作贵人，皆为恩主举荐、赴任催促之象。',
            'foundations' => [
                ['code' => 'cui_guan_messenger', 'title' => '催官使者', 'description' => '日鬼或官星乘白虎，加临日干寄宫、本命或行年。官星按普通五行官鬼表取用，不复用课经鬼墓课特殊日鬼表。'],
                ['code' => 'cui_guan_talisman', 'title' => '催官符', 'description' => '官星加临日干寄宫、本命或行年，且三传组成完整三合局，局五行生官星五行。'],
                ['code' => 'patron_parent_line', 'title' => '恩主举荐·父母爻', 'description' => '父母爻（按日干五行所生五行对应地支）出现在干上神、支上神、初传、中传、末传、本命上神或行年上神任一处。'],
                ['code' => 'patron_noble_as_growth', 'title' => '恩主举荐·长生作贵人', 'description' => '当前所用天乙贵人的天盘地支恰为日干的六壬五行长生支。'],
            ],
            'judgments' => [
                ['label' => '催官使者空亡', 'effect' => 'reduce', 'description' => '催官使者成立后，使者所乘官星落入本旬空亡，力量减损，主虚信、或虽催促而事不实、另有差遣。'],
                ['label' => '四时返本煞', 'effect' => 'reduce', 'description' => '返本煞按《六壬大全》口径：春金局、夏水局、秋火局、冬按《御定六壬直指》「土局与火同」按寅午戌处理。若三传组成当季返本局，主赴任迟滞、迁延反复。'],
                ['label' => '返吟附加', 'effect' => 'reduce', 'description' => '盘面呈现返吟格（课式），第四法已成立后增加此减损断义：赴任得返吟，多主任期难满或赴任反复。'],
            ],
            'sections' => [
                ['title' => '日鬼与鬼墓课日鬼表的区分', 'content' => '第四法沿用普通五行官鬼定义：木日克者为金（申、酉），火日克者为水（亥、子），土日克者为木（寅、卯），金日克者为火（巳、午），水日克者为土（辰、戌、丑、未）。课经「鬼墓课」使用《六壬大全》课经的同性单鬼表（甲日专取申、乙日专取酉、丙日专取子、丁日专取亥、戊日专取卯、己日专取寅、庚日专取午、辛日专取巳、壬日专取辰、癸日专取戌），与第四法不同；本法严格不调用课经鬼墓课的内部日鬼表实现。'],
                ['title' => '催官符的「三传合局生官」解释', 'content' => '催官符按《六壬粹言·毕法赋》解释：辛未日午为官星，三传亥卯未成木局，木局整体生午火官星，故为催官符。因此程序按「三传组成完整三合局，且局五行生官星五行」判定，不按「初、中、末分别生官星」分别生克判定。三合局五行映射：申子辰水、亥卯未木、寅午戌火、巳酉丑金；五行相生：木→火→土→金→水→木。'],
                ['title' => '人物资料与待评估规则', 'content' => 'Route 1、2 的人物路径仅涉及本命/行年一项即可命中；日干路径独立且优先。日干已命中即直接成立。日干不成立且本命/行年全缺时，对应 route 标记待评估。Route 3、4 不依赖本命/行年（Route 3 包含但非必需，Route 4 永不依赖），因此本法几乎不存在 pending 状态。'],
                ['title' => '返本煞只作迟任判断、不作成立入口', 'content' => '返本煞不参与第四法 matcher。若盘面已因 Route 1～4 任一成立而命中第四法，恰好三传又组成当季返本局，则附加一条返本煞减损断义。返本煞绝不单独触发第四法。'],
                ['title' => '《六壬大全》与《六壬粹言》返本煞异说', 'content' => '《六壬大全·毕法赋》返本煞四季口径：春金、夏水、秋火、冬土（《御定六壬直指》按「土局与火同」处理冬为寅午戌）。《六壬粹言》另作春金、夏水、秋木、冬火，与本项目主体依据的《大全》口径不同，属异说。本项目不采用《粹言》异说，仅在研究文档中如实记录。'],
                ['title' => '乙卯昼贵空、己卯夜贵空的特殊边界', 'content' => '「不用」二字是正文明确给出的特例：乙卯日昼贵子落入甲辰旬空时，仅当某个恩主举荐判断完全依靠该昼贵子作父母爻时，不得据此成立；其他父母爻路径不受影响。己卯日夜贵申落入甲申旬空时，长生贵人 route 整体不成立，但 Route 3 父母爻仍可由其它落点成立。这两个特例不扩展到「所有父母爻旬空都不成立」或「所有长生贵人旬空都不成立」。'],
            ],
        ];
    }

    public function match(PanFacts $facts): ?BiFaRuleMatch
    {
        $tianpan = $facts->get('tianpan');
        $rigan = $facts->get('rigan');
        $rizhi = $facts->get('rizhi');
        $period = $facts->get('guirenPeriod');
        $initial = $facts->get('sanchuan0');
        $middle = $facts->get('sanchuan1');
        $final = $facts->get('sanchuan2');

        if (! is_array($tianpan) || ! is_int($rigan) || ! is_int($rizhi)
            || ! in_array($period, ['day', 'night'], true)
            || ! is_int($initial) || ! is_int($middle) || ! is_int($final)) {
            return null;
        }

        $lodging = $facts->stemLodgingBranch($rigan);
        if ($lodging === null) {
            return null;
        }

        $person = self::resolvePerson($facts);
        $officials = self::DAY_OFFICIALS[$rigan] ?? [];
        $parents = self::PARENT_LINES[$rigan] ?? [];
        $origin = self::DAY_ORIGIN[$rigan] ?? null;
        $dayNoble = self::DAY_NOBLE[$rigan] ?? null;
        $nightNoble = self::NIGHT_NOBLE[$rigan] ?? null;
        $currentNoble = $period === 'day' ? $dayNoble : $nightNoble;

        if ($officials === [] || $parents === [] || $origin === null
            || $dayNoble === null || $nightNoble === null || $currentNoble === null) {
            return null;
        }

        // ---------- Route 1：催官使者 ----------
        $baihuBranch = self::branchRiddenByGeneral($facts, self::GENERAL_BAIHU);
        $messengerBranch = $baihuBranch !== null && in_array($baihuBranch, $officials, true)
            ? $baihuBranch
            : null;
        $messengerStem = $messengerBranch !== null
            && ($tianpan[$lodging] ?? null) === $messengerBranch;
        $messengerHit = self::upperHits($tianpan, self::grounds($lodging, $person), $messengerBranch);
        $messengerMatched = $messengerHit;
        $messengerDetails = [];
        if ($messengerStem && $messengerBranch !== null) {
            $messengerDetails[] = sprintf('官星%s乘白虎加临日干寄宫%s', self::BRANCH_NAMES[$messengerBranch], self::BRANCH_NAMES[$lodging]);
        }
        if ($messengerBranch !== null && $person !== null) {
            if ($person['nianming'] !== null && ($tianpan[$person['nianming']] ?? null) === $messengerBranch) {
                $messengerDetails[] = sprintf('官星%s乘白虎加临本命宫%s', self::BRANCH_NAMES[$messengerBranch], self::BRANCH_NAMES[$person['nianming']]);
            }
            if ($person['xingnian'] !== null && ($tianpan[$person['xingnian']] ?? null) === $messengerBranch) {
                $messengerDetails[] = sprintf('官星%s乘白虎加临行年宫%s', self::BRANCH_NAMES[$messengerBranch], self::BRANCH_NAMES[$person['xingnian']]);
            }
        }
        $messengerIsVoid = $messengerBranch !== null
            && $facts->isBranchXunVoid($messengerBranch) === true;

        $messengerPending = $messengerBranch !== null
            && ! $messengerHit
            && self::isPersonCompletelyMissing($person);

        // ---------- Route 2：催官符 ----------
        // 官星加临日干寄宫、本命或行年任一位置，并三传组成完整三合局，局五行生官星五行。
        $talismanOfficialBranch = null;
        $talismanLocation = null;
        $talismanGrounds = self::grounds($lodging, $person);
        foreach ($talismanGrounds as $key => $ground) {
            $branch = $tianpan[$ground] ?? null;
            if (is_int($branch) && in_array($branch, $officials, true)) {
                $talismanOfficialBranch = $branch;
                $talismanLocation = $key;
                break;
            }
        }
        $officialBranch = $talismanOfficialBranch;
        $officialElement = $officialBranch !== null ? $facts->branchElement($officialBranch) : null;
        $sanheElement = null;
        $sanheTriple = BranchRelations::sanheTriple($initial, $middle, $final);
        if ($sanheTriple !== null) {
            $key = implode(',', $sanheTriple);
            $sanheElement = self::SANHE_ELEMENT_MAP[$key] ?? null;
        }
        $talismanMatched = $officialBranch !== null
            && $sanheElement !== null
            && $officialElement !== null
            && self::ELEMENT_SHENG[$sanheElement] === $officialElement;

        // 催官符的人物缺失处理：日干路径不成立且本命/行年全缺时标记待评估。
        $talismanStemHit = is_int($tianpan[$lodging] ?? null)
            && in_array($tianpan[$lodging], $officials, true);
        $talismanPending = ! $talismanStemHit
            && self::isPersonCompletelyMissing($person)
            && $sanheElement !== null
            && $officialElement !== null;

        // ---------- Route 3：恩主举荐·父母爻 ----------
        $parentHits = [];
        $parentCandidates = self::parentCandidates($tianpan, $lodging, $rizhi, $initial, $middle, $final, $person);
        foreach ($parentCandidates as $key => $candidate) {
            $branch = $candidate['branch'];
            $label = $candidate['label'];
            if (in_array($branch, $parents, true)) {
                $parentHits[$key] = $candidate;
            }
        }

        // 乙卯日特例：昼贵子落旬空、且本命中 parent 路径只覆盖「昼贵子作父母爻」时，
        // 视为该路径不可用。
        $yiMaoDayNobleVoided = self::isYiMaoDayNobleVoided($facts, $rigan, $period);
        $route3Matched = false;
        $route3FilteredByYiMao = $yiMaoDayNobleVoided && $parentHits !== [];
        if ($route3FilteredByYiMao) {
            // 仅当所有命中位置都依赖空阴昼贵子时，整条 route 才被屏蔽；
            // 任何其它父母爻落点都保留 route 命中。
            $allFromYiMao = true;
            foreach ($parentHits as $hit) {
                if ($hit['branch'] !== self::DAY_NOBLE[$rigan]) {
                    $allFromYiMao = false;
                    break;
                }
            }
            if (! $allFromYiMao) {
                $route3Matched = true;
            }
        } else {
            $route3Matched = $parentHits !== [];
        }

        // ---------- Route 4：长生作贵人 ----------
        $jiMaoOriginVoided = self::isJiMaoOriginVoided($facts, $rigan, $period);
        $route4Matched = $currentNoble === $origin && ! $jiMaoOriginVoided;

        // ---------- Plate patterns (返本煞、返吟) ----------
        $fanyin = $facts->hasPlatePattern('fanyin');
        $seasonal = $facts->seasonalPeriod();
        $fanbenTriple = self::fanbenTripleForSeason($seasonal['key'] ?? null);
        $fanbenHit = $fanbenTriple !== null
            && BranchRelations::sanheTriple($initial, $middle, $final) !== null
            && BranchRelations::sanheTriple($initial, $middle, $final) === $fanbenTriple;

        $matchedRoutes = [];
        $pendingRoutes = [];

        if ($messengerMatched) {
            $matchedRoutes[] = 'cui_guan_messenger';
        } elseif ($messengerPending) {
            $pendingRoutes[] = 'cui_guan_messenger';
        }
        if ($talismanMatched) {
            $matchedRoutes[] = 'cui_guan_talisman';
        } elseif ($talismanPending) {
            $pendingRoutes[] = 'cui_guan_talisman';
        }
        if ($route3Matched) {
            $matchedRoutes[] = 'patron_parent_line';
        }
        if ($route4Matched) {
            $matchedRoutes[] = 'patron_noble_as_growth';
        }

        if ($matchedRoutes === [] && $pendingRoutes === []) {
            return null;
        }

        $sub = [];
        $sub[] = self::subMatch('cui_guan_messenger', '催官使者',
            $messengerMatched,
            '日鬼或官星乘白虎，加临日干寄宫、本命或行年。',
            $messengerMatched && $messengerDetails !== [] ? implode('；', $messengerDetails) : null,
            $messengerPending,
            $messengerPending);

        $talismanDescription = '官星加临日干寄宫、本命或行年，且三传组成完整三合局，局五行生官星五行。';
        $talismanDetail = null;
        if ($talismanMatched && $officialBranch !== null && $sanheElement !== null) {
            $locationLabel = match ($talismanLocation) {
                'nianming' => '本命',
                'xingnian' => '行年',
                default => '日干寄宫',
            };
            $locationBranch = $talismanLocation === 'nianming'
                ? ($person['nianming'] ?? $lodging)
                : ($talismanLocation === 'xingnian' ? ($person['xingnian'] ?? $lodging) : $lodging);
            $talismanDetail = sprintf(
                '官星%s（%s）临%s%s，三传%s组成%s局（%s），%s生%s。',
                self::BRANCH_NAMES[$officialBranch],
                self::ELEMENT_NAMES[$officialElement ?? 0],
                $locationLabel,
                self::BRANCH_NAMES[$locationBranch],
                self::BRANCH_NAMES[$initial].self::BRANCH_NAMES[$middle].self::BRANCH_NAMES[$final],
                self::ELEMENT_NAMES[$sanheElement],
                implode('、', array_map(static fn (int $b): string => self::BRANCH_NAMES[$b], $sanheTriple ?? [])),
                self::ELEMENT_NAMES[$sanheElement],
                self::ELEMENT_NAMES[$officialElement ?? 0],
            );
        } elseif ($talismanPending) {
            $talismanDetail = '需本命或行年参与判断，当前人物资料不全，无法核实催官符。';
        }
        $sub[] = self::subMatch('cui_guan_talisman', '催官符',
            $talismanMatched,
            $talismanDescription,
            $talismanDetail,
            $talismanPending,
            $talismanPending);

        $parentLocations = [];
        $yiMaoBranch = self::DAY_NOBLE[$rigan] ?? null;
        foreach ($parentHits as $hit) {
            $branch = $hit['branch'];
            // 乙卯日特例：若整条 Route 3 完全由空阴昼贵子承载，则该唯一命中不应展示。
            if ($route3FilteredByYiMao && $yiMaoBranch !== null && $branch === $yiMaoBranch) {
                continue;
            }
            $parentLocations[] = sprintf('%s%s', $hit['label'], self::BRANCH_NAMES[$branch]);
        }
        $parentDetail = $route3Matched && $parentLocations !== []
            ? '父母爻出现在'.implode('、', $parentLocations).'。'
            : ($route3FilteredByYiMao && $parentHits !== []
                ? '仅有乙卯日昼贵子作父母爻路径，但昼贵子旬空，正文明确「不用」，故 Route 3 不成立。'
                : null);
        $sub[] = self::subMatch('patron_parent_line', '恩主举荐·父母爻',
            $route3Matched,
            '父母爻出现在干上神、支上神、初传、中传、末传、本命上神或行年上神任一处。',
            $parentDetail,
            false,
            false);

        $nobleDetail = null;
        if ($route4Matched) {
            $nobleLabel = $period === 'day' ? '昼贵' : '夜贵';
            $nobleDetail = sprintf('当前为%s，贵人地支%s恰为日干六壬五行长生支%s。',
                $nobleLabel, self::BRANCH_NAMES[$currentNoble], self::BRANCH_NAMES[$origin]);
        } elseif ($jiMaoOriginVoided) {
            $nobleDetail = '己卯日夜贵申恰为长生贵人，但申落旬空，正文明确「不用」，故 Route 4 不成立。';
        }
        $sub[] = self::subMatch('patron_noble_as_growth', '恩主举荐·长生作贵人',
            $route4Matched,
            '当前所用天乙贵人的天盘地支恰为日干的六壬五行长生支。',
            $nobleDetail,
            false,
            false);

        return new BiFaRuleMatch(
            code: $this->code(),
            number: $this->law()['number'],
            name: $this->law()['name'],
            summary: $this->law()['summary'],
            subMatches: $sub,
            matchedRoutes: $matchedRoutes,
            pendingRoutes: $pendingRoutes,
            evidence: [
                'rigan' => $rigan,
                'rizhi' => $rizhi,
                'guiren_period' => $period,
                'lodging' => $lodging,
                'officials' => $officials,
                'parents' => $parents,
                'day_origin' => $origin,
                'day_noble' => $dayNoble,
                'night_noble' => $nightNoble,
                'current_noble' => $currentNoble,
                'messenger_branch' => $messengerBranch,
                'messenger_is_void' => $messengerIsVoid,
                'official_branch' => $officialBranch,
                'sanhe_element' => $sanheElement,
                'parent_hits' => array_keys($parentHits),
                'yi_mao_day_noble_voided' => $yiMaoDayNobleVoided,
                'ji_mao_origin_voided' => $jiMaoOriginVoided,
                'fanyin' => $fanyin,
                'season_key' => $seasonal['key'] ?? null,
                'fanben_triple' => $fanbenTriple,
                'fanben_hit' => $fanbenHit,
                'nianming' => $person['nianming'] ?? null,
                'xingnian' => $person['xingnian'] ?? null,
            ],
        );
    }

    /**
     * @return array{nianming: ?int, xingnian: ?int}|null
     */
    private static function resolvePerson(PanFacts $facts): ?array
    {
        $person = $facts->personByRole('querent');
        if ($person === null) {
            return null;
        }

        $nianming = is_int($person['nianming'] ?? null) ? $person['nianming'] : null;
        $xingnian = is_int($person['xingnian'] ?? null) ? $person['xingnian'] : null;
        if ($nianming === null && $xingnian === null) {
            return null;
        }

        return ['nianming' => $nianming, 'xingnian' => $xingnian];
    }

    private static function isPersonCompletelyMissing(?array $person): bool
    {
        return $person === null;
    }

    /**
     * @return array<string, int> 日干寄宫 -> "日干寄宫", 本命/行年 -> 各自标签
     */
    private static function grounds(int $lodging, ?array $person): array
    {
        $grounds = ['lodging' => $lodging];
        if ($person !== null) {
            if ($person['nianming'] !== null) {
                $grounds['nianming'] = $person['nianming'];
            }
            if ($person['xingnian'] !== null) {
                $grounds['xingnian'] = $person['xingnian'];
            }
        }

        return $grounds;
    }

    /**
     * @param  array<string, int>  $grounds
     */
    private static function upperHits(array $tianpan, array $grounds, ?int $target): bool
    {
        if ($target === null) {
            return false;
        }
        foreach ($grounds as $g) {
            if (($tianpan[$g] ?? null) === $target) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, array{label: string, branch: int}>
     */
    private static function parentCandidates(
        array $tianpan,
        int $lodging,
        int $rizhi,
        int $initial,
        int $middle,
        int $final,
        ?array $person,
    ): array {
        $candidates = [
            'ganShang' => ['label' => '干上神', 'branch' => (int) ($tianpan[$lodging] ?? -1)],
            'zhiShang' => ['label' => '支上神', 'branch' => (int) ($tianpan[$rizhi] ?? -1)],
            'initial' => ['label' => '初传', 'branch' => $initial],
            'middle' => ['label' => '中传', 'branch' => $middle],
            'final' => ['label' => '末传', 'branch' => $final],
        ];
        if ($person !== null) {
            if ($person['nianming'] !== null) {
                $candidates['nianming'] = ['label' => '本命上神', 'branch' => (int) ($tianpan[$person['nianming']] ?? -1)];
            }
            if ($person['xingnian'] !== null) {
                $candidates['xingnian'] = ['label' => '行年上神', 'branch' => (int) ($tianpan[$person['xingnian']] ?? -1)];
            }
        }

        // 过滤无效值（tianpan 不存在）
        return array_filter($candidates, static fn (array $c): bool => $c['branch'] >= 0 && $c['branch'] <= 11);
    }

    /**
     * 返回指定天将所乘的地支（即 tianjiang 中 general 所在的 position 对应的 tianpan 值）。
     *
     * 「白虎乘申」= 天将序号 7 所在的位置 g，使得 tianpan[g] == 8（申）。
     *
     * 注意：现有 `PanFacts::generalRidingBranch($branch)` 签名是传「地支」找「天将」；
     * 第四法需要反方向，因此独立实现。
     */
    private static function branchRiddenByGeneral(PanFacts $facts, int $general): ?int
    {
        $tianjiang = $facts->get('tianjiang');
        $tianpan = $facts->get('tianpan');
        if (! is_array($tianjiang) || ! is_array($tianpan)) {
            return null;
        }
        foreach ($tianjiang as $position => $assigned) {
            if (is_int($assigned) && $assigned === $general && is_int($position)) {
                return $tianpan[$position] ?? null;
            }
        }

        return null;
    }

    /**
     * 乙卯日特例：乙日昼贵为子；若日支为卯，且昼贵子落旬空，则 Route 3 中
     * 仅依靠昼贵子作父母爻的路径视为「不用」。
     */
    private static function isYiMaoDayNobleVoided(PanFacts $facts, int $rigan, string $period): bool
    {
        if ($rigan !== 1 || $period !== 'day') {
            return false;
        }
        $rizhi = $facts->get('rizhi');
        if (! is_int($rizhi) || $rizhi !== 3) {
            return false;
        }

        return $facts->isBranchXunVoid(self::DAY_NOBLE[$rigan]) === true;
    }

    /**
     * 己卯日特例：己日夜贵为申，恰为日干长生支；若日支为卯，且申旬空，则 Route 4 不成立。
     */
    private static function isJiMaoOriginVoided(PanFacts $facts, int $rigan, string $period): bool
    {
        if ($rigan !== 5 || $period !== 'night') {
            return false;
        }
        $rizhi = $facts->get('rizhi');
        if (! is_int($rizhi) || $rizhi !== 3) {
            return false;
        }

        return $facts->isBranchXunVoid(self::NIGHT_NOBLE[$rigan]) === true;
    }

    /**
     * 返本煞四季局（《六壬大全》口径；冬按《御定六壬直指》「土局与火同」处理）。
     *
     *  - 春 → 巳酉丑金局
     *  - 夏 → 申子辰水局
     *  - 秋 → 寅午戌火局
     *  - 冬 → 寅午戌火局（土局与火同）
     *
     * @return list<int>|null
     */
    private static function fanbenTripleForSeason(?string $seasonKey): ?array
    {
        return match ($seasonKey) {
            'spring' => [3, 7, 11],
            'summer' => [0, 4, 8],
            'autumn' => [2, 6, 10],
            'winter' => [2, 6, 10],
            default => null,
        };
    }

    /**
     * @return array{code: string, title: string, description: string, matched: bool, detail: ?string, requires_people: bool, people_missing: bool}
     */
    private static function subMatch(
        string $code,
        string $title,
        bool $matched,
        string $description,
        ?string $detail,
        bool $requiresPeople,
        bool $peopleMissing,
    ): array {
        return [
            'code' => $code,
            'title' => $title,
            'description' => $description,
            'matched' => $matched,
            'detail' => $detail,
            'requires_people' => $requiresPeople,
            'people_missing' => $peopleMissing,
        ];
    }
}
