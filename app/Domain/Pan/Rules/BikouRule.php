<?php

namespace App\Domain\Pan\Rules;

use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;

/**
 * 文件作用：按《六壬大全》闭口课正文的三个并列入口判断闭口课。
 *
 * 规则边界：只实现须发用的通用主体；“六甲占盗贼，责玄武，逆数四神，不论发用”
 * 带有盗贼占类前提，当前排盘上下文没有问事类型，故不并入主体。
 */
final class BikouRule implements PanRule
{
    private const XUANWU = 9;

    protected const RULE_CODE = 'lesson.bikou';

    protected const NAME = '闭口课';

    protected const GROUP = '六十四课';

    protected const DESCRIPTION = '旬尾加旬首发用，或旬首乘玄武发用，或旬首位上神乘玄武发用。';

    protected const GUA = '谦';

    protected const GUA_SYMBOL = '䷎';

    protected const XIANG = '禁口不语，事迹难明。寻人没影，失物潜藏。告贵弗允，论讼不平。孕生哑子，占事终成。';

    /** @var list<string> */
    private const UNCOVERED = [
        '“六甲占盗贼，责玄武，旬首为阳神，逆数四神，六癸旬首为阴神，不论发用”依赖问事类型，尚未实现',
        '传逢六合、朱雀、白虎及日禄作闭口等象义与吉凶修证尚未实现',
    ];

    public function code(): string
    {
        return self::RULE_CODE;
    }

    public function match(PanFacts $facts): ?RuleMatch
    {
        $dayIndex = $facts->sexagenaryDayIndex();
        $xunHead = $facts->dayXunHeadBranch();
        $initial = $facts->get('sanchuan0');
        $tianpan = $facts->get('tianpan');
        $generals = $facts->get('tianjiang');

        if ($dayIndex === null || $xunHead === null || ! is_int($initial)
            || ! is_array($tianpan) || ! is_array($generals)
            || ! isset($tianpan[$xunHead], $generals[$xunHead])
            || ! is_int($tianpan[$xunHead]) || ! is_int($generals[$xunHead])) {
            return null;
        }

        $xunTail = ($xunHead + 9) % 12;
        $upperAtXunHead = $tianpan[$xunHead];
        $generalAtXunHead = $generals[$xunHead];
        $initialGround = array_search($initial, $tianpan, true);

        if ($initialGround === false || ! isset($generals[$initialGround]) || ! is_int($generals[$initialGround])) {
            return null;
        }

        $initialGeneral = $generals[$initialGround];
        $tailOnHead = $initial === $xunTail && $upperAtXunHead === $xunTail;
        $headRidingXuanwu = $initial === $xunHead && $initialGeneral === self::XUANWU;
        $headUpperRidingXuanwu = $initial === $upperAtXunHead && $generalAtXunHead === self::XUANWU;

        if (! ($tailOnHead || $headRidingXuanwu || $headUpperRidingXuanwu)) {
            return null;
        }

        $paths = [];
        if ($tailOnHead) {
            $paths[] = '旬尾加旬首发用';
        }
        if ($headRidingXuanwu) {
            $paths[] = '旬首乘玄武发用';
        }
        if ($headUpperRidingXuanwu) {
            $paths[] = '旬首位上神乘玄武发用';
        }

        $branch = static fn (int $i): string => PanCalculator::$dizhi[$i] ?? '?';
        $general = static fn (int $i): string => PanCalculator::$tianjiang[$i] ?? '?';

        return new RuleMatch(
            code: $this->code(),
            name: self::NAME,
            group: self::GROUP,
            description: self::DESCRIPTION,
            gua: self::GUA,
            guaSymbol: self::GUA_SYMBOL,
            xiang: self::XIANG,
            evidence: [
                'day_index' => $dayIndex,
                'xun_head' => $xunHead,
                'xun_tail' => $xunTail,
                'initial' => $initial,
                'initial_general' => $initialGeneral,
                'upper_at_xun_head' => $upperAtXunHead,
                'general_at_xun_head' => $generalAtXunHead,
                'tail_on_head' => $tailOnHead,
                'head_riding_xuanwu' => $headRidingXuanwu,
                'head_upper_riding_xuanwu' => $headUpperRidingXuanwu,
                'paths' => $paths,
                'foundations' => [[
                    'title' => implode('、', $paths),
                    'detail' => "占日属{$branch($xunHead)}旬，旬首为{$branch($xunHead)}，旬尾为{$branch($xunTail)}；地盘{$branch($xunHead)}宫的上神为{$branch($upperAtXunHead)}，初传为{$branch($initial)}；初传乘{$general($initialGeneral)}，旬首位乘{$general($generalAtXunHead)}。",
                ]],
                'judgments' => [],
                'uncovered' => self::UNCOVERED,
            ],
        );
    }
}
