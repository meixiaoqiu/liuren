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
        '六阳遇退间传、六阴遇昼夜及出户、盈阳、励明、回明等附格，仅保留为课后判断，尚未建立跨课的动态联动。',
        '六阴所述“将乘后合元、支干遇盗气、弹射发用、坐空”与源消根断，需要统一神将、盗气、空亡及年命事实后再程序化。',
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
            'judgments' => [
                ['code' => 'liuyang', 'effect' => 'increase', 'label' => '六阳课', 'description' => '正文谓六阳“私凶公吉，官遇升迁”；宜占天庭尊长之事。'],
                ['code' => 'liuyin', 'effect' => 'reduce', 'label' => '六阴课', 'description' => '正文谓六阴“公凶私利，病患缠延”；宜卑下、阴谋奸私之事，病者死。'],
                ['code' => 'liuyang_retreat_jianchuan', 'effect' => 'neutral', 'label' => '六阳遇退间传', 'description' => '正文甲午例称六阳遇退间传为倒拔蛇、悖戾，并见财引入中末鬼乡；保留为需跨课核验的附格。'],
                ['code' => 'liuyin_special_transmissions', 'effect' => 'neutral', 'label' => '六阴昼夜与间传附格', 'description' => '正文列夜传昼、昼将入夜及出户、盈阳、励明、回明等，指出不可一概以昏迷断之。'],
                ['code' => 'five_yang_yin_fate_fill', 'effect' => 'neutral', 'label' => '五阳五阴与年命填实', 'description' => '正文称“以人年命定之”；机器判定语义尚未验证，不参与 matcher。'],
                ['code' => 'source_exhaustion_root_severance', 'effect' => 'reduce', 'label' => '源消根断', 'description' => '正文以五阴相续、盗气迤逦脱去，并本命缘不摄而死为断；尚待人物与盗气事实统一。'],
            ],
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
            'judgments' => array_map(fn (array $judgment) => [...$judgment, 'matched' => $judgment['code'] === $type], $this->definition()['judgments']),
            'uncovered' => self::UNCOVERED,
        ]);
    }
}
