@php
    $branchNames = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];
    $stemNames = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];

    $dayStem = $trace['day_stem'] ?? null;
    $dayBranch = $trace['day_branch'] ?? null;
    $stemLodging = $trace['day_stem_lodging_branch'] ?? null;
    $dayUpper = $trace['day_upper'] ?? null;
    $branchUpper = $trace['branch_upper'] ?? null;
    $initial = $trace['initial'] ?? null;

    $isTianGang = (bool) ($trace['is_tian_gang'] ?? false);
    $isTianKui = (bool) ($trace['is_tian_kui'] ?? false);
    $onDayStem = (bool) ($trace['on_day_stem'] ?? false);
    $onDayBranch = (bool) ($trace['on_day_branch'] ?? false);

    $faYongLabel = match (true) {
        $isTianGang => '天罡（辰）',
        $isTianKui => '天魁（戌）',
        default => '?',
    };
    $faYongBranchName = is_int($initial) ? ($branchNames[$initial] ?? '?') : '?';

    $addPath = match (true) {
        $onDayStem && $onDayBranch => '同时加临日干寄宫与日支',
        $onDayStem => '加临日干寄宫',
        $onDayBranch => '加临日支',
        default => '未加临日干或日支',
    };
@endphp
<section class="pan-block mt-4 bg-base-200/45 px-4 py-4 sm:px-5" aria-label="斩关判断过程">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="font-semibold">斩关判断（正文最小严格口径）</h3>
            <p class="mt-1 text-sm text-base-content/55">
                日干{{ $stemNames[$dayStem] ?? '?' }}寄宫{{ $branchNames[$stemLodging] ?? '?' }}、干上神{{ $branchNames[$dayUpper] ?? '?' }}；
                日支{{ $branchNames[$dayBranch] ?? '?' }}、支上神{{ $branchNames[$branchUpper] ?? '?' }}；
                初传 = {{ $faYongBranchName }}（{{ $faYongLabel }}）。
            </p>
        </div>
        <x-badge value="斩关成立" class="badge-primary badge-soft" />
    </div>

    <div class="mt-4 grid gap-3 md:grid-cols-2">
        <div class="pan-block bg-base-100/75 px-4 py-4">
            <strong>① 发用为魁或罡</strong>
            <p class="mt-3 text-sm leading-6 text-base-content/65">
                初传 = {{ $faYongBranchName }}，
                <strong>{{ $isTianGang || $isTianKui ? '成立（' . $faYongLabel . '）' : '不成立' }}</strong>。<br>
                （魁=戌、罡=辰；九宗门"发用"即初传 sanchuan0）
            </p>
        </div>
        <div class="pan-block bg-base-100/75 px-4 py-4">
            <strong>② 魁/罡 加临日干寄宫或日支</strong>
            <p class="mt-3 text-sm leading-6 text-base-content/65">
                日干{{ $stemNames[$dayStem] ?? '?' }}的寄宫位为
                {{ $branchNames[$stemLodging] ?? '?' }}，
                该位天盘上神 = {{ $branchNames[$dayUpper] ?? '?' }}；
                日支{{ $branchNames[$dayBranch] ?? '?' }}位天盘上神 = {{ $branchNames[$branchUpper] ?? '?' }}。
                初传{{ $faYongBranchName }}{{ $addPath }}：
                <strong>{{ $onDayStem || $onDayBranch ? '成立' : '不成立' }}</strong>。
            </p>
        </div>
        <div class="pan-block bg-base-100/75 px-4 py-4 md:col-span-2">
            <strong>③ 最终结论（① AND ② 同时成立）</strong>
            <p class="mt-3 text-sm leading-6 text-base-content/65">
                成立：发用为{{ $faYongLabel }}（{{ $faYongBranchName }}），{{ $addPath }}，
                满足《六壬大全》"魁罡加日辰发用"正文最小严格口径。
            </p>
            @if (! empty($trace['uncovered']) && is_array($trace['uncovered']))
                <details class="mt-3 text-sm text-base-content/55">
                    <summary class="cursor-pointer">衍生结构（暂不作为必要条件）</summary>
                    <ul class="mt-2 list-disc pl-5">
                        @foreach ($trace['uncovered'] as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </details>
            @endif
        </div>
    </div>
</section>
