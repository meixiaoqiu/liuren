<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/**
 * 文件作用：判断第63课杂状课，并按《六壬大全》冻结口径复原初传纯杂、物色与太玄数。
 *
 * 规则边界：凡正常盘存在合法初传即成课；纯杂、五行、颜色、太玄数及旺衰修正均为取象信息，
 * 不反过来收紧 matcher。子午卯酉固定为纯，其余八支为杂，不套用现代地支藏干表。
 */
final class ZazhuangRule implements PanRule
{
    public const RULE_CODE = 'lesson.zazhuang';

    public const NAME = '杂状课';

    public const GROUP = '六十四课';

    public const DESCRIPTION = '凡正常课取初传，辨其纯杂，并据初传及所临地盘取五行、颜色与太玄数。';

    public const XIANG = '五行阴阳，万物纯杂，凶视救神，吉防害鬼。数目日期，颜色物类，觅物寻人，克应可取。';

    /** @var list<int> */
    public const PURE_BRANCHES = [0, 6, 3, 9];

    /** @var array<int, list<string>> */
    public const BRANCH_COLORS = [
        0 => ['黑'], 1 => ['黄'], 2 => ['绯', '碧'], 3 => ['青'],
        4 => ['黄'], 5 => ['斑点绿'], 6 => ['赤'], 7 => ['黄'],
        8 => ['白', '黑'], 9 => ['白'], 10 => ['黄'], 11 => ['淡青'],
    ];

    /** @var array<int, int> */
    public const TAI_XUAN_NUMBERS = [9, 8, 7, 6, 5, 4, 9, 8, 7, 6, 5, 4];

    /** @var array<int, int> */
    public const BIRTH_BRANCHES_BY_STEM = [11, 11, 2, 2, 8, 8, 5, 5, 8, 8];

    /** @var array<int, int> */
    public const DEATH_BRANCHES_BY_STEM = [7, 7, 10, 10, 4, 4, 1, 1, 4, 4];

    /** @var array<string, int|float> */
    public const NUMBER_MULTIPLIERS = ['旺' => 10, '相' => 2, '休' => 1, '囚' => 0.5, '死' => 0.5];

    private const ELEMENT_NAMES = ['木', '火', '土', '金', '水'];

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function definition(): array
    {
        return [
            'description' => self::DESCRIPTION,
            'xiang' => self::XIANG,
            'foundations' => [
                ['code' => 'valid_initial', 'title' => '凡正常课取初传', 'description' => '只要排盘存在 0 至 11 范围内的合法初传，即成立杂状课。'],
            ],
            'judgments' => [],
        ];
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $initial = $facts->get('sanchuan0');
        if (! is_int($initial) || $initial < 0 || $initial > 11) {
            return null;
        }

        $ground = $facts->heavenBranchGroundPosition($initial);
        $groundValid = is_int($ground) && $ground >= 0 && $ground <= 11;

        $dayStem = $facts->get('rigan');
        $dayStemValid = is_int($dayStem) && $dayStem >= 0 && $dayStem <= 9;
        $pure = in_array($initial, self::PURE_BRANCHES, true);
        $mixedSubtype = null;
        $mixedSubtypeLabel = null;
        if (! $pure) {
            if ($dayStemValid && self::BIRTH_BRANCHES_BY_STEM[$dayStem] === $initial) {
                [$mixedSubtype, $mixedSubtypeLabel] = ['birth_mixed', '生杂'];
            } elseif ($dayStemValid && self::DEATH_BRANCHES_BY_STEM[$dayStem] === $initial) {
                [$mixedSubtype, $mixedSubtypeLabel] = ['death_mixed', '死杂'];
            } else {
                [$mixedSubtype, $mixedSubtypeLabel] = ['ordinary_mixed', '杂'];
            }
        }

        $initialName = self::branchName($initial);
        $groundName = $groundValid ? self::branchName($ground) : null;
        $dayStemName = $dayStemValid ? (PanCalculator::$tiangan[$dayStem] ?? '?') : null;
        $upperElement = $facts->branchElement($initial);
        $lowerElement = $groundValid ? $facts->branchElement($ground) : null;
        $upperNumber = self::TAI_XUAN_NUMBERS[$initial];
        $lowerNumber = $groundValid ? self::TAI_XUAN_NUMBERS[$ground] : null;
        $baseNumber = is_int($lowerNumber) ? $upperNumber * $lowerNumber : null;
        $seasonalState = $facts->branchSeasonalState($initial);
        $multiplier = is_string($seasonalState) ? (self::NUMBER_MULTIPLIERS[$seasonalState] ?? null) : null;
        $adjustedNumber = $multiplier === null || $baseNumber === null ? null : self::adjustedNumber($baseNumber, $multiplier);
        $classificationEvidence = $pure
            ? "初传{$initialName}，属于子午卯酉四仲纯神。"
            : $this->mixedEvidence($initialName, $dayStemName, $mixedSubtype, $mixedSubtypeLabel);

        return new RuleMatch(
            code: self::RULE_CODE,
            name: self::NAME,
            group: self::GROUP,
            description: self::DESCRIPTION,
            gua: null,
            guaSymbol: null,
            xiang: self::XIANG,
            evidence: [
                'initial' => $initial,
                'initial_name' => $initialName,
                'ground' => $groundValid ? $ground : null,
                'ground_name' => $groundName,
                'purity' => $pure ? 'pure' : 'mixed',
                'purity_label' => $pure ? '纯' : '杂',
                'mixed_subtype' => $mixedSubtype,
                'mixed_subtype_label' => $mixedSubtypeLabel,
                'day_stem' => $dayStemValid ? $dayStem : null,
                'day_stem_name' => $dayStemName,
                'upper_element' => $upperElement,
                'upper_element_name' => self::elementName($upperElement),
                'lower_element' => $lowerElement,
                'lower_element_name' => self::elementName($lowerElement),
                'upper_colors' => self::BRANCH_COLORS[$initial],
                'lower_colors' => $groundValid ? self::BRANCH_COLORS[$ground] : [],
                'upper_number' => $upperNumber,
                'lower_number' => $lowerNumber,
                'base_number' => $baseNumber,
                'seasonal_state' => $seasonalState,
                'number_multiplier' => $multiplier,
                'adjusted_number' => $adjustedNumber,
                'imagery' => [
                    'classification' => $classificationEvidence,
                    'elements' => "上神{$initialName}".self::elementName($upperElement).($groundValid ? "，下神{$groundName}".self::elementName($lowerElement).'。' : '；当前盘缺少可读的加临地盘。'),
                    'colors' => '上'.implode('、', self::BRANCH_COLORS[$initial]).($groundValid ? '，下'.implode('、', self::BRANCH_COLORS[$ground]).'。' : '；下神颜色待加临地盘可读后计算。'),
                    'numbers' => $groundValid ? "{$initialName}{$upperNumber} × {$groundName}{$lowerNumber} = {$baseNumber}" : '当前盘缺少可读的加临地盘，暂不能计算基础数。',
                ],
                'foundations' => [
                    ['code' => 'valid_initial', 'title' => '凡正常课取初传', 'description' => '存在合法初传即成立杂状课。', 'matched' => true, 'evidence' => "当前初传为{$initialName}。"],
                ],
                'judgments' => [],
                'uncovered' => [
                    '“凶视救神，吉防害鬼”的具体程序语义尚未冻结。',
                    '生杂所主、死杂所主等传统占断，目前仅保留古籍说明，尚未结构化为动态 judgment。',
                    '数目如何依据不同占类解释为日、月、距离等单位，尚未建立统一程序模型。',
                    '“觅物寻人，克应可取”的具体取法尚未程序化。',
                ],
            ],
        );
    }

    public static function adjustedNumber(int $baseNumber, int|float $multiplier): int|float
    {
        return $baseNumber * $multiplier;
    }

    private function mixedEvidence(string $initialName, ?string $dayStemName, ?string $subtype, ?string $label): string
    {
        return match ($subtype) {
            'birth_mixed' => "初传{$initialName}为杂；{$dayStemName}日以{$initialName}为长生，属{$label}。",
            'death_mixed' => "初传{$initialName}为杂；{$dayStemName}日以{$initialName}为墓，属{$label}。",
            default => "初传{$initialName}为杂，本盘不属于对应日干五行的生杂或死杂。",
        };
    }

    private static function branchName(int $branch): string
    {
        return PanCalculator::$dizhi[$branch] ?? '?';
    }

    private static function elementName(?int $element): string
    {
        return is_int($element) ? (self::ELEMENT_NAMES[$element] ?? '?') : '?';
    }
}
