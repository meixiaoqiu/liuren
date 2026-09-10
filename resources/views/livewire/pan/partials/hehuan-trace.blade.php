@php
    $generalNames = ['贵人', '螣蛇', '朱雀', '六合', '勾陈', '青龙', '天空', '白虎', '太常', '玄武', '太阴', '天后'];
    $auspiciousGeneralNames = ['贵人', '六合', '青龙', '太常', '太阴', '天后'];
    $stemNames = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];
    $branchNames = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];

    $sanheLabels = [
        '0,4,8' => '申子辰（水局）',
        '1,5,9' => '巳酉丑（金局）',
        '2,6,10' => '寅午戌（火局）',
        '3,7,11' => '亥卯未（木局）',
    ];

    $dayStem = $trace['day_stem'] ?? null;
    $dayBranch = $trace['day_branch'] ?? null;
    $dayUpper = $trace['day_upper'] ?? null;
    $dayUpperStem = $trace['day_upper_stem'] ?? null;
    $initial = $trace['initial'] ?? null;
    $middle = $trace['middle'] ?? null;
    $final = $trace['final'] ?? null;
    $initialHexesUpper = (bool) ($trace['initial_hexes_upper'] ?? false);
    $sanchuanSanhe = (bool) ($trace['sanchuan_sanhe'] ?? false);
    $sanchuanSanheTriple = $trace['sanchuan_sanhe_triple'] ?? null;

    $nianming = $trace['nianming'] ?? null;
    $xingnian = $trace['xingnian'] ?? null;

    $hasContext = ($nianming !== null && ($nianming['ground'] ?? null) !== null)
        || ($xingnian !== null && ($xingnian['ground'] ?? null) !== null);

    $renderYearMingSlot = function (?array $slot, string $label) use ($branchNames, $generalNames): string {
        if ($slot === null || ($slot['ground'] ?? null) === null) {
            return "占人{$label}缺数据。";
        }
        $groundBranch = $branchNames[$slot['ground']] ?? '?';
        $upperBranch = $branchNames[$slot['upper']] ?? '?';
        $gen = $slot['general'];
        $genName = is_int($gen) ? ($generalNames[$gen] ?? '?') : '?';
        $isAuspicious = (bool) ($slot['auspicious'] ?? false);
        $verdict = $isAuspicious ? '乘六吉将' : '不乘六吉将';

        return "地盘{$label}宫为{$groundBranch}，宫上神为{$upperBranch}，乘{$genName}，{$verdict}。";
    };

    if (! $hasContext) {
        $verdictLabel = '尚未判断：需要占人本命与行年信息（出生时间与性别），当前未进行判断。';
        $verdictBadge = '尚未判断';
    } else {
        $nmAuspicious = ($nianming['auspicious'] ?? false) === true;
        $xnAuspicious = ($xingnian['auspicious'] ?? false) === true;
        if ($nmAuspicious && $xnAuspicious) {
            $sanheLabel = is_array($sanchuanSanheTriple)
                ? ($sanheLabels[implode(',', $sanchuanSanheTriple)] ?? '未识别三合局')
                : '三合';
            $verdictLabel = "成立：日干{$stemNames[$dayStem]}与干上神寄宫{$stemNames[$dayUpperStem]}作天干五合，初传与干上神作地支六合，日支、初传与中传或末传构成{$sanheLabel}，且本命、行年两宫上神均乘六吉将（正文最小严格口径）。";
            $verdictBadge = '合欢成立';
        } else {
            $verdictLabel = '不成立：年命信息存在，但本命或行年宫上神未乘六吉将。';
            $verdictBadge = '合欢不成立';
        }
    }
@endphp

<section class="pan-block mt-4 bg-base-200/45 px-4 py-4 sm:px-5" aria-label="合欢判断过程">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="font-semibold">合欢判断（正文最小严格口径）</h3>
            <p class="mt-1 text-sm text-base-content/55">
                日干{{ $stemNames[$dayStem] ?? '?' }}、日支{{ $branchNames[$dayBranch] ?? '?' }}；
                干上神为{{ $branchNames[$dayUpper] ?? '?' }}（寄宫天干为{{ $stemNames[$dayUpperStem] ?? '?' }}）；
                三传为{{ $branchNames[$initial] ?? '?' }}、{{ $branchNames[$middle] ?? '?' }}、{{ $branchNames[$final] ?? '?' }}。
            </p>
        </div>
        <x-badge :value="$verdictBadge" class="badge-primary badge-soft" />
    </div>

    <div class="mt-4 grid gap-3 md:grid-cols-2">
        <div class="pan-block bg-base-100/75 px-4 py-4">
            <strong>① 天干作合</strong>
            <p class="mt-3 text-sm leading-6 text-base-content/65">
                日干{{ $stemNames[$dayStem] ?? '?' }}的寄宫位为
                {{ isset($trace['day_stem_lodging_branch']) ? $branchNames[$trace['day_stem_lodging_branch']] : '?' }}，
                干上神为{{ $branchNames[$dayUpper] ?? '?' }}，
                该地支按本日所在旬所寄天干为{{ $stemNames[$dayUpperStem] ?? '?' }}，
                与日干{{ $stemNames[$dayStem] ?? '?' }}作天干五合
                （甲己、乙庚、丙辛、丁壬、戊癸），
                <strong>成立</strong>。
            </p>
        </div>
        <div class="pan-block bg-base-100/75 px-4 py-4">
            <strong>② 支六合发用</strong>
            <p class="mt-3 text-sm leading-6 text-base-content/65">
                初传{{ $branchNames[$initial] ?? '?' }}
                与干上神{{ $branchNames[$dayUpper] ?? '?' }}
                作地支六合：
                <strong>{{ $initialHexesUpper ? '成立' : '不成立' }}</strong>。<br>
                （六合对：子丑、寅亥、卯戌、辰酉、巳申、午未）
            </p>
        </div>
        <div class="pan-block bg-base-100/75 px-4 py-4">
            <strong>③ 支三合发用（日支、初传与中传或末传构成完整三合）</strong>
            <p class="mt-3 text-sm leading-6 text-base-content/65">
                日支为{{ $branchNames[$dayBranch] ?? '?' }}，
                三传（{{ $branchNames[$initial] ?? '?' }}、{{ $branchNames[$middle] ?? '?' }}、{{ $branchNames[$final] ?? '?' }}）
                中是否由初传参与，并与日支及中传或末传共同构成完整三合局：
                <strong>{{ $sanchuanSanhe ? '成立' : '不成立' }}</strong>。<br>
                @if ($sanchuanSanhe && is_array($sanchuanSanheTriple))
                    命中三合局为<strong>{{ $sanheLabels[implode(',', $sanchuanSanheTriple)] ?? '未识别三合局' }}</strong>。
                @endif
            </p>
        </div>
        <div class="pan-block bg-base-100/75 px-4 py-4">
            <strong>④ 本命宫上神</strong>
            <p class="mt-3 text-sm leading-6 text-base-content/65">{{ $renderYearMingSlot($nianming, '本命') }}</p>
        </div>
        <div class="pan-block bg-base-100/75 px-4 py-4">
            <strong>⑤ 行年宫上神</strong>
            <p class="mt-3 text-sm leading-6 text-base-content/65">{{ $renderYearMingSlot($xingnian, '行年') }}</p>
        </div>
        <div class="pan-block bg-base-100/75 px-4 py-4 md:col-span-2">
            <strong>⑥ 最终结论（以上五项同时成立）</strong>
            <p class="mt-3 text-sm leading-6 text-base-content/65">{{ $verdictLabel }}</p>
            @if(! empty($trace['uncovered']) && is_array($trace['uncovered']))
                <details class="mt-3 text-sm text-base-content/55">
                    <summary class="cursor-pointer">衍生结构（暂不作为必要条件）</summary>
                    <ul class="mt-2 list-disc pl-5">
                        @foreach($trace['uncovered'] as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </details>
            @endif
        </div>
    </div>
</section>
