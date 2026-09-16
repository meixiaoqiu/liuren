<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\BranchRelations;
use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/**
 * 文件作用：集中维护第58课全局课与五格的共同判定、顺逆三合和已程序化课义判断。
 * 规则边界：四方局（寅卯辰等）不纳入全局；三合犯杀在“刑”公共能力补齐前不伪实现。
 */
final class QuanjuSupport
{
    /** @var list<int> */
    public const SEASON_EARTH_BRANCHES = [1, 4, 7, 10];

    /** @var array<string, array{slug:string,name:string,element:int,element_name:string,representative:int,canonical:list<int>}> */
    private const SANHE_GRIDS = [
        '0,4,8' => ['slug' => 'runxia', 'name' => '润下格', 'element' => 4, 'element_name' => '水', 'representative' => 0, 'canonical' => [8, 0, 4]],
        '1,5,9' => ['slug' => 'congge', 'name' => '从革格', 'element' => 3, 'element_name' => '金', 'representative' => 9, 'canonical' => [5, 9, 1]],
        '2,6,10' => ['slug' => 'yanshang', 'name' => '炎上格', 'element' => 1, 'element_name' => '火', 'representative' => 6, 'canonical' => [2, 6, 10]],
        '3,7,11' => ['slug' => 'quzhi', 'name' => '曲直格', 'element' => 0, 'element_name' => '木', 'representative' => 3, 'canonical' => [11, 3, 7]],
    ];

    /** @return list<int>|null */
    public static function transmissions(PanFacts $facts): ?array
    {
        $values = [$facts->get('sanchuan0'), $facts->get('sanchuan1'), $facts->get('sanchuan2')];

        return array_reduce($values, fn (bool $valid, mixed $value): bool => $valid && is_int($value), true)
            ? $values
            : null;
    }

    /** @return array{slug:string,name:string,element:int,element_name:string,representative:int,canonical:?list<int>,type:'sanhe'|'jiase',transmissions:list<int>}|null */
    public static function classify(PanFacts $facts): ?array
    {
        $transmissions = self::transmissions($facts);
        if ($transmissions === null) {
            return null;
        }

        $sorted = $transmissions;
        sort($sorted);
        $key = implode(',', $sorted);
        if (isset(self::SANHE_GRIDS[$key])) {
            return [...self::SANHE_GRIDS[$key], 'type' => 'sanhe', 'transmissions' => $transmissions];
        }

        if (array_reduce(
            $transmissions,
            fn (bool $valid, int $branch): bool => $valid && in_array($branch, self::SEASON_EARTH_BRANCHES, true),
            true,
        )) {
            return [
                'slug' => 'jiase',
                'name' => '稼穑格',
                'element' => 2,
                'element_name' => '土',
                'representative' => 4,
                'canonical' => null,
                'type' => 'jiase',
                'transmissions' => $transmissions,
            ];
        }

        return null;
    }

    /** @param array{type:string,canonical:?array,transmissions:list<int>} $grid */
    public static function direction(array $grid): ?string
    {
        if ($grid['type'] !== 'sanhe' || ! is_array($grid['canonical'])) {
            return null;
        }

        $forward = self::rotations($grid['canonical']);

        return in_array($grid['transmissions'], $forward, true) ? 'forward' : 'reverse';
    }

    /** @param list<int> $cycle @return list<list<int>> */
    private static function rotations(array $cycle): array
    {
        return [
            $cycle,
            [$cycle[1], $cycle[2], $cycle[0]],
            [$cycle[2], $cycle[0], $cycle[1]],
        ];
    }

    /** @param list<int> $transmissions */
    public static function transmissionNames(array $transmissions): string
    {
        return implode('→', array_map(fn (int $branch): string => PanCalculator::$dizhi[$branch] ?? '?', $transmissions));
    }

    /** @param array{name:string,element_name:string,type:string,transmissions:list<int>} $grid */
    public static function detail(array $grid): string
    {
        $transmissionNames = self::transmissionNames($grid['transmissions']);

        return $grid['type'] === 'jiase'
            ? "三传{$transmissionNames}全部属于辰戌丑未四季土，成{$grid['name']}。"
            : "三传{$transmissionNames}完整构成三合{$grid['element_name']}局，成{$grid['name']}。";
    }

    /** @return array{day_upper:int,branch_upper:int}|null */
    public static function upperGods(PanFacts $facts): ?array
    {
        $dayStem = $facts->get('rigan');
        $dayBranch = $facts->get('rizhi');
        $tianpan = $facts->get('tianpan');
        if (! is_int($dayStem) || ! is_int($dayBranch) || ! is_array($tianpan)) {
            return null;
        }

        $lodging = $facts->stemLodgingBranch($dayStem);
        if (! is_int($lodging) || ! isset($tianpan[$lodging], $tianpan[$dayBranch])
            || ! is_int($tianpan[$lodging]) || ! is_int($tianpan[$dayBranch])) {
            return null;
        }

        return ['day_upper' => $tianpan[$lodging], 'branch_upper' => $tianpan[$dayBranch]];
    }

    /** @param array<string,mixed> $grid @return list<array<string,mixed>> */
    public static function judgments(PanFacts $facts, array $grid): array
    {
        $judgments = [];
        $transmissions = $grid['transmissions'];
        $direction = self::direction($grid);

        if ($direction !== null) {
            $judgments[] = [
                'code' => $direction === 'forward' ? 'sanhe_forward' : 'sanhe_reverse',
                'effect' => $direction === 'forward' ? 'increase' : 'reduce',
                'label' => $direction === 'forward' ? '顺三合' : '逆三合',
                'description' => $direction === 'forward' ? '顺三合理势自然。' : '逆三合事主乖违。',
                'matched' => true,
                'evidence' => '三传'.self::transmissionNames($transmissions).'，判为'.($direction === 'forward' ? '顺' : '逆').'三合。',
            ];
        }

        $gridState = $facts->branchSeasonalState($grid['representative']);
        if (is_string($gridState)) {
            $judgments[] = [
                'code' => 'grid_seasonal_state_'.self::stateCode($gridState),
                'effect' => in_array($gridState, ['旺', '相'], true) ? 'increase' : (in_array($gridState, ['囚', '死'], true) ? 'reduce' : 'neutral'),
                'label' => "{$grid['element_name']}局时令{$gridState}",
                'description' => '记录全局所属五行在当前四立／土旺十八日口径下的旺相休囚死。',
                'matched' => true,
                'evidence' => "{$grid['name']}属{$grid['element_name']}，当前{$grid['element_name']}行处于{$gridState}。",
            ];
        }

        $initialState = $facts->branchSeasonalState($transmissions[0]);
        if (is_string($initialState)) {
            $judgments[] = [
                'code' => 'initial_seasonal_state_'.self::stateCode($initialState),
                'effect' => in_array($initialState, ['旺', '相'], true) ? 'increase' : (in_array($initialState, ['囚', '死'], true) ? 'reduce' : 'neutral'),
                'label' => "初传时令{$initialState}",
                'description' => '初传有气、无气与整个局五行旺衰分开记录。',
                'matched' => true,
                'evidence' => (PanCalculator::$dizhi[$transmissions[0]] ?? '?')."为初传，当前处于{$initialState}。",
            ];
        }

        $liuheEvidence = self::liuheAidEvidence($facts, $transmissions);
        if ($liuheEvidence !== []) {
            $judgments[] = [
                'code' => 'liuhe_assists_grid',
                'effect' => 'increase',
                'label' => '六合助局',
                'description' => '一传与干支上神作六合，或三传所乘天将见六合，主有人相助成合。',
                'matched' => true,
                'evidence' => implode('；', $liuheEvidence).'。',
            ];
        }

        array_push($judgments, ...self::gridSpecificJudgments($facts, $grid));

        return $judgments;
    }

    /** @param list<int> $transmissions @return list<string> */
    private static function liuheAidEvidence(PanFacts $facts, array $transmissions): array
    {
        $evidence = [];
        $uppers = self::upperGods($facts);
        foreach ($transmissions as $index => $branch) {
            $position = ['初传', '中传', '末传'][$index];
            $branchName = PanCalculator::$dizhi[$branch] ?? '?';
            if ($uppers !== null) {
                foreach (['day_upper' => '干上神', 'branch_upper' => '支上神'] as $key => $label) {
                    $upper = $uppers[$key];
                    if (BranchRelations::isLiuhe($branch, $upper)) {
                        $evidence[] = $position.$branchName.'与'.$label.(PanCalculator::$dizhi[$upper] ?? '?').'六合';
                    }
                }
            }

            if ($facts->generalRidingBranch($branch) === 3) {
                $evidence[] = $position.$branchName.'乘六合天将';
            }
        }

        return array_values(array_unique($evidence));
    }

    /** @param array<string,mixed> $grid @return list<array<string,mixed>> */
    private static function gridSpecificJudgments(PanFacts $facts, array $grid): array
    {
        $stem = $facts->get('rigan');
        if (! is_int($stem)) {
            return [];
        }

        $stemName = PanCalculator::$tiangan[$stem] ?? '?';
        $judgments = [];
        $add = static function (array &$items, string $code, string $effect, string $label, string $description, string $evidence): void {
            $items[] = compact('code', 'effect', 'label', 'description', 'evidence') + ['matched' => true];
        };

        if ($grid['slug'] === 'runxia') {
            if (in_array($stem, [0, 1], true)) {
                $add($judgments, 'runxia_wood_day_shengqi', 'increase', '木日得生气', '润下水局生木日。', "日干{$stemName}属木，润下水局生木。 ");
            }
            if (in_array($stem, [6, 7], true)) {
                $add($judgments, 'runxia_metal_day_daoqi', 'reduce', '金日为盗气', '金日生润下水局，日干之气外泄。', "日干{$stemName}属金，金生水局。 ");
            }
        }

        if ($grid['slug'] === 'yanshang') {
            if (in_array($stem, [4, 5], true)) {
                $add($judgments, 'yanshang_earth_day_shengqi', 'increase', '土日得生气', '炎上火局生土日。', "日干{$stemName}属土，火局生土。 ");
            }
            if (in_array($stem, [0, 1], true)) {
                $add($judgments, 'yanshang_wood_day_daoqi', 'reduce', '木日为盗气', '木日生炎上火局，日干之气外泄。', "日干{$stemName}属木，木生火局。 ");
            }
            if (in_array($stem, [6, 7], true)) {
                $add($judgments, 'yanshang_gengxin_kill', 'reduce', '庚辛日带杀', '庚辛属金，炎上火局克金。', "日干{$stemName}属金，受火局所克。 ");
            }
            if (in_array($stem, [8, 9], true)) {
                $add($judgments, 'yanshang_rengui_zimugui', 'reduce', '壬癸日子母鬼', '壬癸属水，虽以火为财，火又生土反制水，传统称子母鬼。', "日干{$stemName}属水，炎上火局兼见子母鬼义。 ");
            }

            $tianpan = $facts->get('tianpan');
            if (is_array($tianpan) && ($tianpan[2] ?? null) === 10) {
                $add($judgments, 'yanshang_xu_on_yin', 'reduce', '戌加寅：墓临生', '炎上格见戌加寅，为墓临生。', '天盘戌加临地盘寅。');
            }
            if (is_array($tianpan) && ($tianpan[10] ?? null) === 6) {
                $add($judgments, 'yanshang_wu_on_xu', 'reduce', '午加戌：入墓', '炎上格见午加戌，为火入墓。', '天盘午加临地盘戌。');
            }
        }

        if ($grid['slug'] === 'quzhi') {
            if ($stem === 5) {
                $add($judgments, 'quzhi_ji_rooted', 'increase', '己日根固', '曲直木局见己日，传统称根固。', '日干为己。');
            }
            if ($stem === 3) {
                $add($judgments, 'quzhi_ding_withered', 'reduce', '丁日枝枯', '丁火泄木，曲直格传统称枝枯。', '日干为丁。');
            }
            if ($stem === 7) {
                $add($judgments, 'quzhi_xin_material', 'increase', '辛日成器', '辛金裁木，曲直格传统取成器之义。', '日干为辛。');
            }

            $tianpan = $facts->get('tianpan');
            $structures = [
                [11, 7, 'quzhi_wei_on_hai', '未加亥：直', '未加亥，传统取“直”义。'],
                [7, 11, 'quzhi_hai_on_wei', '亥加未：曲', '亥加未，传统取“曲”义。'],
                [11, 3, 'quzhi_mao_on_hai', '卯加亥：先曲后直', '卯加亥，主始难终易。'],
                [7, 3, 'quzhi_mao_on_wei', '卯加未：先直后曲', '卯加未，主有始无终。'],
            ];
            if (is_array($tianpan)) {
                foreach ($structures as [$ground, $upper, $code, $label, $description]) {
                    if (($tianpan[$ground] ?? null) === $upper) {
                        $add($judgments, $code, 'neutral', $label, $description, '天盘'.(PanCalculator::$dizhi[$upper] ?? '?').'加临地盘'.(PanCalculator::$dizhi[$ground] ?? '?').'。');
                    }
                }
            }
        }

        if ($grid['slug'] === 'congge') {
            if (in_array($stem, [8, 9], true)) {
                $add($judgments, 'congge_water_day_shengqi', 'increase', '水日得生气', '从革金局生水日。', "日干{$stemName}属水，金局生水。 ");
            }
            if (in_array($stem, [4, 5], true)) {
                $add($judgments, 'congge_earth_day_daoqi', 'reduce', '土日为盗气', '土日生从革金局，日干之气外泄。', "日干{$stemName}属土，土生金局。 ");
            }
        }

        if ($grid['slug'] === 'jiase') {
            if (in_array($stem, [4, 5], true)) {
                $add($judgments, 'jiase_wuji_harder', 'reduce', '戊己日更艰难', '《订讹》谓稼穑占主沉滞，戊己日更属艰难。', "日干为{$stemName}。 ");
            }
            if (in_array($stem, [8, 9], true)) {
                $add($judgments, 'jiase_rengui_release', 'resolve', '壬癸日脱难', '《订讹》称壬癸日为脱难杀。', "日干为{$stemName}。 ");
            }
            if ($facts->generalRidingBranch(3) === 3) {
                $add($judgments, 'jiase_thunder_god', 'resolve', '雷神解滞', '太冲卯乘六合天将，按《订讹》称雷神，主变化。', '太冲卯所乘天将为六合。');
            }
        }

        return array_map(static function (array $item): array {
            if (isset($item['evidence']) && is_string($item['evidence'])) {
                $item['evidence'] = trim($item['evidence']);
            }

            return $item;
        }, $judgments);
    }

    private static function stateCode(string $state): string
    {
        return match ($state) {
            '旺' => 'wang',
            '相' => 'xiang',
            '休' => 'xiu',
            '囚' => 'qiu',
            '死' => 'si',
            default => 'unknown',
        };
    }
}
