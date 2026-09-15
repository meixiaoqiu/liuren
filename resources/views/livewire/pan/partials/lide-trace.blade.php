@php
    $branch = fn ($value) => is_int($value) ? (\App\Services\PanCalculator::$dizhi[$value] ?? '?') : '?';
    $general = fn ($value) => is_int($value) ? (\App\Services\PanCalculator::$tianjiang[$value] ?? '?') : '?';
    $side = ['front' => '贵前', 'rear' => '贵后', 'center' => '贵人居中'];
    $gods = is_array($trace['four_gods'] ?? null) ? $trace['four_gods'] : [];
    $pattern = $trace['pattern'] ?? null;
    $patternLabel = $trace['pattern_label'] ?? '分型资料不足';
@endphp

<section class="pan-block mt-4 overflow-hidden bg-base-200/45" aria-label="励德分型与四课神位置">
    <h3 class="px-4 pt-4 font-semibold sm:px-5">励德分型与四课神位置</h3>

    <div class="border-t border-base-300/70 px-4 py-4 sm:px-5">
        <h4 class="text-sm font-semibold tracking-wide text-base-content/70">课体分型</h4>
        <p class="mt-1 text-sm leading-6 text-base-content/55">
            贵前、贵后按所乘天将判断；分型不改变励德课是否成立，只用于辨别微服、蹉跎及相关吉凶。
        </p>

        @if ($gods !== [])
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                @foreach (['day_yang', 'day_yin', 'branch_yang', 'branch_yin'] as $key)
                    @php($item = $gods[$key] ?? null)
                    @if (is_array($item))
                        <div class="pan-block bg-base-100/75 px-4 py-3 text-sm leading-6">
                            <strong>{{ $item['label'] ?? $key }}</strong>
                            <p class="mt-1 text-base-content/65">
                                上神{{ $branch($item['upper'] ?? null) }}，乘{{ $general($item['general'] ?? null) }}，位置：{{ $side[$item['side'] ?? ''] ?? '未知' }}。
                            </p>
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="mt-3 pan-block bg-base-100/75 px-4 py-3 text-sm leading-6">
                <strong>课体分型：{{ $patternLabel }}</strong>
                @if ($pattern === 'mixed')
                    <p class="mt-1 text-base-content/60">本盘未落入微服、蹉跎、阳前阴后、阴前阳后四种完整分型，但励德课仍然成立。</p>
                @elseif ($pattern === 'weifu' || $pattern === 'cuotuo')
                    <p class="mt-1 text-base-content/60">本盘同时符合相应格的条件，该格另行展示。</p>
                @endif
            </div>
        @else
            <p class="mt-3 text-sm text-base-content/55">四课资料不足，未进行课体分型；贵人临卯酉的成课判断不受影响。</p>
        @endif
    </div>
</section>