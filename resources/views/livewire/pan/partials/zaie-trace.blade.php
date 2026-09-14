@php
    $monthBranch = $trace['month_branch'] ?? null;
    $yearBranch = $trace['year_branch'] ?? null;
    $initial = $trace['initial'] ?? null;
    $shensha = $trace['shensha'] ?? [];
    $matchedKeys = $trace['matched_keys'] ?? [];
    $monthName = is_int($monthBranch) ? ($dizhi[$monthBranch] ?? '?') : '?';
    $yearName = is_int($yearBranch) ? ($dizhi[$yearBranch] ?? '?') : '?';
    $initialName = is_int($initial) ? ($dizhi[$initial] ?? '?') : '?';
@endphp
<section class="pan-block mt-4 bg-base-200/45 px-4 py-4 sm:px-5" aria-label="灾厄判断过程">
    <h3 class="font-semibold">灾厄判断</h3>
    <div class="mt-4 grid gap-3 md:grid-cols-3">
        <div class="pan-block bg-base-100/75 px-4 py-4">
            <strong>月建</strong>
            <p class="mt-2 text-sm leading-6 text-base-content/70">{{ $monthName }}</p>
        </div>
        <div class="pan-block bg-base-100/75 px-4 py-4">
            <strong>太岁</strong>
            <p class="mt-2 text-sm leading-6 text-base-content/70">{{ $yearName }}</p>
        </div>
        <div class="pan-block bg-base-100/75 px-4 py-4">
            <strong>初传</strong>
            <p class="mt-2 text-sm leading-6 text-base-content/70">{{ $initialName }}</p>
        </div>
    </div>
    <div class="mt-4">
        <strong class="text-sm">九煞定位</strong>
        <div class="mt-2 overflow-x-auto"><table class="table table-sm">
            <thead><tr><th>神煞</th><th>所在地支</th><th>是否发用</th></tr></thead>
            <tbody>
                @foreach (['sangche' => '丧车（又名丧魂）', 'youhun' => '游魂', 'fuyang' => '伏殃（又名天鬼煞）', 'bingfu' => '病符', 'sangmen' => '丧门', 'diaoke' => '吊客', 'sanqiu' => '三丘', 'wumu' => '五墓', 'suihu' => '岁虎'] as $key => $label)
                    <tr class="{{ in_array($key, $matchedKeys, true) ? 'text-primary font-semibold' : 'text-base-content/55' }}">
                        <th>{{ $label }}</th>
                        <td>{{ is_int($shensha[$key] ?? null) ? ($dizhi[$shensha[$key]] ?? '?') : '?' }}</td>
                        <td>{{ in_array($key, $matchedKeys, true) ? '发用' : '未发用' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table></div>
    </div>
    @if (! empty($trace['uncovered']))
        <details class="mt-4 text-sm text-base-content/55">
            <summary class="cursor-pointer">附加条件与尚未实现项</summary>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($trace['uncovered'] as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </details>
    @endif
</section>
