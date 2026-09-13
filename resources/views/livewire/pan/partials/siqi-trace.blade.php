@php
    $routes = $trace['routes'] ?? [];
    $sikeUpper = $trace['sike_upper_branches'] ?? [];
    $gang = $trace['gang_ground'] ?? null;
@endphp
<section class="pan-block mt-4 bg-base-200/45 px-4 py-4 sm:px-5" aria-label="死奇判断过程">
    <h3 class="font-semibold">死奇判断</h3>
    <div class="mt-4 grid gap-3 md:grid-cols-2">
        <div class="pan-block bg-base-100/75 px-4 py-4">
            <strong>初传与四课上神</strong>
            <p class="mt-2 text-sm leading-6 text-base-content/70">初传即{{ $dizhi[$trace['initial']] }}（天罡辰发用）。</p>
            <p class="mt-1 text-sm leading-6 text-base-content/70">四课上神：第1课 {{ $dizhi[$sikeUpper[0] ?? 12] ?? '?' }}、第2课 {{ $dizhi[$sikeUpper[1] ?? 12] ?? '?' }}、第3课 {{ $dizhi[$sikeUpper[2] ?? 12] ?? '?' }}、第4课 {{ $dizhi[$sikeUpper[3] ?? 12] ?? '?' }}。</p>
        </div>
        <div class="pan-block bg-base-100/75 px-4 py-4">
            <strong>命中路线</strong>
            @if (count($routes) === 0)
                <p class="mt-2 text-sm leading-6 text-base-content/55">无</p>
            @else
                <ul class="mt-2 space-y-1 text-sm leading-6 text-base-content/70">
                    @foreach ($routes as $route)
                        <li>第{{ $route['lesson'] }}课（{{ $route['polarity'] }}）：sike[{{ $route['lesson'] * 2 - 1 }}] = 辰。</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
    <p class="mt-4 text-sm leading-6 text-base-content/70">天罡所临地盘：<strong>{{ $dizhi[$gang] }}</strong>（按 array_search(4, tianpan, true) 求得）。</p>
    @if (! empty($trace['judgments']))
        <div class="mt-4"><strong class="text-sm">附加判断</strong>
            <ul class="mt-2 space-y-2">
                @foreach ($trace['judgments'] as $judgment)
                    <li class="text-sm leading-6"><span class="font-medium">{{ $judgment['label'] }}</span>：{{ $judgment['evidence'] }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @if (! empty($trace['uncovered']))
        <details class="mt-4 text-sm text-base-content/55">
            <summary class="cursor-pointer">异说与尚未覆盖项</summary>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($trace['uncovered'] as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </details>
    @endif
</section>
