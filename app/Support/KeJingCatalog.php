<?php

namespace App\Support;

use App\Extensions\KeJingExtensionRegistry;
use LogicException;

/** 文件作用：维护第11～64课的稳定公开身份，并合并外部插件贡献的专家资产。 */
final class KeJingCatalog
{
    /** @var array<string, 'daquan'|'other'> */
    private const SOURCE_EXAMPLE_TYPES = [
        '《六壬大全》' => 'daquan', '《六壬大全》正文' => 'daquan',
        '《六壬大全·灾厄课》正文' => 'daquan', '《六壬大全》正文（识典底本 130 节）' => 'daquan',
        '《六壬大全》正文（详例，已有可排课例）' => 'daquan', '《六壬大全》正文（盘式简例）' => 'daquan',
        '《六壬大全》一旬周遍格' => 'daquan', '《六壬大全》正文（详例）' => 'daquan',
        '《六壬大全》正文标准课例' => 'daquan', '《六壬大全》泆女正文例' => 'daquan',
        '《袖中金》（识典底本 130 节附录）' => 'other', '《观月经》（识典底本130节附录）' => 'other',
        '《御定六壬直指》' => 'other', '《订讹》' => 'other', '《袖中金》' => 'other', '《灵觉经》' => 'other',
    ];

    /** @return list<array{number:int,name:string,code:string,slug:string,summary:string}> */
    public static function lessons(): array
    {
        $lessons = [
            self::identity(11, '三光课', 'sanguang'), self::identity(12, '三阳课', 'sanyang'),
            self::identity(13, '三奇课', 'sanqi'), self::identity(14, '六仪课', 'liuyi'),
            self::identity(15, '时泰课', 'shitai'), self::identity(16, '龙德课', 'longde'),
            self::identity(17, '官爵课', 'guanjue'), self::identity(18, '富贵课', 'fugui'),
            self::identity(19, '轩盖课', 'xuangai'), self::identity(20, '铸印课', 'zhuyin'),
            self::identity(21, '斫轮课', 'zhuolun'), self::identity(22, '引从课', 'yincong'),
            self::identity(23, '亨通课', 'hengtong'), self::identity(24, '繁昌课', 'fanchang'),
            self::identity(25, '荣华课', 'rong_hua'), self::identity(26, '德庆课', 'de_qing'),
            self::identity(27, '合欢课', 'he_huan'), self::identity(28, '和美课', 'he_mei'),
            self::identity(29, '斩关课', 'zhan_guan'), self::identity(30, '闭口课', 'bikou'),
            self::identity(31, '游子课', 'youzi'), self::identity(32, '三交课', 'sanjiao'),
            self::identity(33, '赘婿课', 'zhuixu'), self::identity(34, '冲破课', 'chongpo'),
            self::identity(35, '淫泆课', 'yinyi'), self::identity(36, '芜淫课', 'wuyin'),
            self::identity(37, '解离课', 'jieli'), self::identity(38, '度厄课', 'due'),
            self::identity(39, '无禄绝嗣课', 'wulu_juesi'), self::identity(40, '迍福课', 'zhunfu'),
            self::identity(41, '侵害课', 'qinhai'), self::identity(42, '刑伤课', 'xingshang'),
            self::identity(43, '二烦课', 'erfan'), self::identity(44, '天祸课', 'tianhuo'),
            self::identity(45, '天狱课', 'tianyu'), self::identity(46, '天寇课', 'tiankou'),
            self::identity(47, '天网课', 'tianwang'), self::identity(48, '魄化课', 'pohua'),
            self::identity(49, '三阴课', 'sanyin'), self::identity(50, '龙战课', 'longzhan'),
            self::identity(51, '死奇课', 'siqi'), self::identity(52, '灾厄课', 'zaie'),
            self::identity(53, '殃咎课', 'yangjiu'), self::identity(54, '九丑课', 'jiuchou'),
            self::identity(55, '鬼墓课', 'guimu'), self::identity(56, '励德课', 'lide'),
            self::identity(57, '盘珠课', 'panzhu'), self::identity(58, '全局课', 'quanju'),
            self::identity(59, '玄胎课', 'xuantai'), self::identity(60, '连珠课', 'lianzhu'),
            self::identity(61, '间传课', 'jianchuan'), self::identity(62, '六纯课', 'liuchun'),
            self::identity(63, '杂状课', 'zazhuang'), self::identity(64, '物类课', 'wulei'),
        ];

        $extensions = self::extensionRegistry();
        if ($extensions === null) {
            return $lessons;
        }

        foreach ($lessons as &$lesson) {
            $summary = $extensions->summaryFor($lesson['code']);
            if (is_string($summary) && trim($summary) !== '') {
                $lesson['summary'] = $summary;
            }
            $metadata = $extensions->lessonMetadataFor($lesson['code']);
            if ($metadata !== null) {
                $lesson['gua'] = $metadata['gua'];
                $lesson['guaSymbol'] = $metadata['guaSymbol'];
            }
            $cases = $extensions->casesForLesson($lesson['code']);
            if ($cases !== []) {
                $lesson['cases'] = $cases;
            }
            $examples = $extensions->sourceExamplesForLesson($lesson['code']);
            if ($examples !== []) {
                $lesson['source_examples'] = self::normalizeSourceExamples($examples);
            }
        }
        unset($lesson);

        return $lessons;
    }

    /** @return array{case:array<string,mixed>,lesson:array<string,mixed>}|null */
    public static function findCase(string $caseId): ?array
    {
        foreach (self::lessons() as $lesson) {
            foreach ($lesson['cases'] ?? [] as $case) {
                if (($case['case_id'] ?? null) === $caseId) {
                    return ['case' => $case, 'lesson' => $lesson];
                }
            }
        }

        return null;
    }

    /** @return array{case:array<string,mixed>,lesson:array<string,mixed>}|null */
    public static function findReferenceCase(string $caseId): ?array
    {
        $found = self::findCase($caseId);

        return $found !== null && ($found['case']['status'] ?? 'executable') === 'reference_only' ? $found : null;
    }

    /** @return array{number:int,name:string,code:string,slug:string,summary:string} */
    private static function identity(int $number, string $name, string $code): array
    {
        return ['number' => $number, 'name' => $name, 'code' => 'lesson.'.$code, 'slug' => str_replace('_', '-', $code), 'summary' => '', 'gua' => null, 'guaSymbol' => null];
    }

    /** @param list<array<string,mixed>> $examples @return list<array<string,mixed>> */
    private static function normalizeSourceExamples(array $examples): array
    {
        foreach ($examples as &$example) {
            if (isset($example['source_type'])) {
                continue;
            }
            $source = $example['source'] ?? null;
            if ($source === null) {
                $example['source_type'] = 'other';

                continue;
            }
            if (! is_string($source) || ! array_key_exists($source, self::SOURCE_EXAMPLE_TYPES)) {
                throw new LogicException('未登记的课经旁证来源：'.(is_scalar($source) ? (string) $source : get_debug_type($source)));
            }
            $example['source_type'] = self::SOURCE_EXAMPLE_TYPES[$source];
        }
        unset($example);

        return $examples;
    }

    private static function extensionRegistry(): ?KeJingExtensionRegistry
    {
        if (! function_exists('app')) {
            return null;
        }
        try {
            $registry = app(KeJingExtensionRegistry::class);
        } catch (\Throwable) {
            return null;
        }

        return $registry instanceof KeJingExtensionRegistry ? $registry : null;
    }
}
