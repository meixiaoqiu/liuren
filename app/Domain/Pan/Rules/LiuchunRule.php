<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/** 文件作用：按《六壬大全》冻结定义判断第62课六纯课。 */
final class LiuchunRule implements PanRule
{
    use LessonDefinitionDefaults;

    public const RULE_CODE = 'lesson.liuchun';

    public const NAME = '六纯课';

    public const GROUP = '六十四课';

    public const GUA = '革';

    public const GUA_SYMBOL = '䷰';

    public const DESCRIPTION = '四课上神皆阳（或皆阴），初传为其中一课发用，且中传、末传同为阳（或阴），为六阳课（或六阴课）。';

    public const XIANG = '六阳动达，如登三天。私凶公吉，官遇升迁。六阴朦昧，似涉重渊。公凶私利，病患缠延。';

    private const UNCOVERED = [
        '五阳、五阴及年命填实暂未程序化；原因是古籍扩展规则存在，但具体“填之”的机器判定语义尚未完成课例验证。',
        '六阳遇退间传、夜传昼及六阴遇昼将入夜、出户、盈阳、励明、回明等附格尚未程序化。',
        '后合元、盗气、弹射发用、坐空及源消根断尚未程序化。',
        '初传、中传逢空时，君子畏之减力、常人赖之省力及末事得理等身份化占断尚未程序化。',
    ];

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function definition(): array
    {
        return [
            'description' => self::DESCRIPTION,
            'xiang' => self::XIANG,
            'foundations' => [[
                'code' => 'liuchun_route',
                'title' => '六阳／六阴两条独立入口',
                'description' => '四课四个上神、初传以外的中传和末传同属阳，或同属阴；初传必须是四课上神之一。',
            ]],
            'judgments' => [],
        ];
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $sike = $facts->get('sike');
        $transmissions = array_map(fn (string $key) => $facts->get($key), ['sanchuan0', 'sanchuan1', 'sanchuan2']);
        $validBranch = static fn ($branch): bool => is_int($branch) && $branch >= 0 && $branch <= 11;
        if (! is_array($sike) || count($sike) < 8 || ! array_is_list($sike)
            || count(array_filter($transmissions, $validBranch)) !== 3) {
            return null;
        }

        $upper = [$sike[1] ?? null, $sike[3] ?? null, $sike[5] ?? null, $sike[7] ?? null];
        if (count(array_filter($upper, $validBranch)) !== 4
            || ! in_array($transmissions[0], $upper, true)) {
            return null;
        }

        $allYang = count(array_filter($upper, fn (int $branch) => $branch % 2 === 0)) === 4
            && $transmissions[1] % 2 === 0 && $transmissions[2] % 2 === 0;
        $allYin = count(array_filter($upper, fn (int $branch) => $branch % 2 === 1)) === 4
            && $transmissions[1] % 2 === 1 && $transmissions[2] % 2 === 1;
        if (! $allYang && ! $allYin) {
            return null;
        }

        $type = $allYang ? 'liuyang' : 'liuyin';
        $branchName = static fn (int $branch): string => PanCalculator::$dizhi[$branch] ?? '?';
        $names = implode('、', array_map($branchName, $upper));

        return new RuleMatch(self::RULE_CODE, self::NAME, self::GROUP, self::DESCRIPTION, gua: self::GUA, guaSymbol: self::GUA_SYMBOL, xiang: self::XIANG, evidence: [
            'type' => $type,
            'sike_upper_branches' => $upper,
            'transmissions' => $transmissions,
            'initial_from_sike_upper' => true,
            'foundations' => [[
                'code' => 'liuchun_route', 'title' => $allYang ? '六阳课' : '六阴课',
                'description' => '四课上神与中、末传同属一阴阳；初传由四课上神发用。',
                'matched' => true,
                'evidence' => "四课上神为{$names}；三传为".implode('、', array_map($branchName, $transmissions)).'。',
            ]],
            'judgments' => [],
            'uncovered' => self::UNCOVERED,
        ]);
    }
}
