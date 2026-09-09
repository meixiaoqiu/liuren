@php
    $virtueLabels = [
        'stem' => '日干德',
        'branch' => '日支德',
        'heavenly' => '天德',
        'monthly' => '月德',
    ];
    $matchedVirtueLabels = [];
    foreach (($trace['virtue_types'] ?? []) as $type) {
        $matchedVirtueLabels[] = $virtueLabels[$type] ?? $type;
    }
    $generalNames = ['贵人', '螣蛇', '朱雀', '六合', '勾陈', '青龙', '天空', '白虎', '太常', '玄武', '太阴', '天后'];
    $auspiciousGeneralNames = ['贵人', '六合', '青龙', '太常', '太阴', '天后'];
    $stemNames = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];
    $branchNames = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];
    $initial = $trace['initial_branch'] ?? null;
    $initialBranch = is_int($initial) ? ($branchNames[$initial] ?? '?') : '?';
    $initialGeneral = $trace['initial_general'] ?? null;
    $initialGeneralName = is_int($initialGeneral) ? ($generalNames[$initialGeneral] ?? '?') : '?';
    $initialRidesAuspicious = (bool) ($trace['initial_rides_auspicious'] ?? false);
    $isAuspiciousGeneral = in_array($initialGeneral, [0, 3, 5, 8, 10, 11], true);

    $nianming = $trace['nianming'] ?? null;
    $xingnian = $trace['xingnian'] ?? null;
    $matchedVia = $trace['matched_via'] ?? [];
    $matchedViaLabels = [];
    foreach ($matchedVia as $via) {
        $matchedViaLabels[] = $via === 'nianming' ? '本命' : '行年';
    }

    $renderYearMingSlot = function (?array $slot, string $label) use ($branchNames, $generalNames, $initialBranch): string {
        if ($slot === null || ($slot['ground'] ?? null) === null) {
            return "占人{$label}缺数据。";
        }
        $groundBranch = $branchNames[$slot['ground']] ?? '?';
        $upper = $slot['upper'];
        $upperBranch = $branchNames[$upper] ?? '?';
        $gen = $slot['general'];
        $genName = is_int($gen) ? ($generalNames[$gen] ?? '?') : '?';
        $isAuspicious = (bool) ($slot['auspicious'] ?? false);
        $isInitialUpper = (bool) ($slot['initial_is_upper'] ?? false);
        $verdict = $isAuspicious ? '乘六吉将' : '不乘六吉将';
        $initialCompare = $isInitialUpper
            ? "（等于初传{$initialBranch}）"
            : "（不等于初传{$initialBranch}）";

        return "地盘{$label}={$groundBranch} → {$label}宫上神={$upperBranch}，乘{$genName}，{$verdict}{$initialCompare}。";
    };

    $hasContext = ($nianming !== null && ($nianming['ground'] ?? null) !== null)
        || ($xingnian !== null && ($xingnian['ground'] ?? null) !== null);

    if (! $hasContext) {
        $verdictLabel = '尚未判断：需要占人本命与行年信息（出生时间与性别），当前未进行判断。';
        $verdictBadge = '尚未判断';
    } elseif ($matchedVia === []) {
        // 上下文存在但 initial 既不在本命宫上神也不在行年宫上神
        $verdictLabel = '不成立：四德之一发用且乘六吉将，但发用德神未加临占人本命宫或行年宫。';
        $verdictBadge = '德庆不成立';
    } else {
        $viaText = implode(' + ', $matchedViaLabels);
        $verdictLabel = "成立：发用德神（{$initialBranch}）乘{$initialGeneralName}（吉将），通过 {$viaText} 路径加临占人本命或行年。";
        $verdictBadge = '德庆成立';
    }
@endphp

<section class="pan-block mt-4 bg-base-200/45 px-4 py-4 sm:px-5" aria-label="德庆判断过程">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="font-semibold">德庆判断</h3>
            <p class="mt-1 text-sm text-base-content/55">
                日干{{ $stemNames[$trace['day_stem'] ?? 0] }}、日支{{ $branchNames[$trace['day_branch'] ?? 0] }}、月支{{ $branchNames[$trace['month_branch'] ?? 0] }}；
                初传{{ $initialBranch }}乘{{ $initialGeneralName }}{{ $initialRidesAuspicious ? '（六吉将）' : '（非六吉将）' }}。
            </p>
        </div>
        <x-badge :value="$verdictBadge" class="badge-primary badge-soft" />
    </div>

    <div class="mt-4 grid gap-3 md:grid-cols-2">
        <div class="pan-block bg-base-100/75 px-4 py-4">
            <strong>① 四类德神取值</strong>
            <p class="mt-3 text-sm leading-6 text-base-content/65">
                日干德 = {{ $branchNames[$trace['stem_virtue'] ?? 0] }}（{{ $stemNames[$trace['day_stem'] ?? 0] }}日所到）、
                日支德 = {{ $branchNames[$trace['branch_virtue'] ?? 0] }}（{{ $branchNames[$trace['day_branch'] ?? 0] }}日起巳顺行）、
                天德 = {{ $branchNames[$trace['heavenly_virtue'] ?? 0] }}（{{ $branchNames[$trace['month_branch'] ?? 0] }}月所到）、
                月德 = {{ $branchNames[$trace['monthly_virtue'] ?? 0] }}（{{ $branchNames[$trace['month_branch'] ?? 0] }}月所到）。<br>
                本盘初传{{ $initialBranch }}<strong>{{ empty($matchedVirtueLabels) ? '未命中任何德神' : '命中' . implode('+', $matchedVirtueLabels) }}</strong>。
            </p>
        </div>
        <div class="pan-block bg-base-100/75 px-4 py-4">
            <strong>② 初传天将</strong>
            <p class="mt-3 text-sm leading-6 text-base-content/65">
                初传{{ $initialBranch }}所乘天将 = <strong>{{ $initialGeneralName }}</strong>。
                {{ $isAuspiciousGeneral ? '属于六吉将（' . implode('、', $auspiciousGeneralNames) . '）。' : '不属于六吉将（' . implode('、', $auspiciousGeneralNames) . '）。' }}
            </p>
        </div>
        <div class="pan-block bg-base-100/75 px-4 py-4">
            <strong>③ 本命宫上神</strong>
            <p class="mt-3 text-sm leading-6 text-base-content/65">{{ $renderYearMingSlot($nianming, '本命') }}</p>
        </div>
        <div class="pan-block bg-base-100/75 px-4 py-4">
            <strong>④ 行年宫上神</strong>
            <p class="mt-3 text-sm leading-6 text-base-content/65">{{ $renderYearMingSlot($xingnian, '行年') }}</p>
        </div>
        <div class="pan-block bg-base-100/75 px-4 py-4 md:col-span-2">
            <strong>⑤ 最终结论</strong>
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
