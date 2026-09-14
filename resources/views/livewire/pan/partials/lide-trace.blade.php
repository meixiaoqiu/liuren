@php
    $branch = fn ($value) => is_int($value) ? (\App\Services\PanCalculator::$dizhi[$value] ?? '?') : '?';
    $general = fn ($value) => is_int($value) ? (\App\Services\PanCalculator::$tianjiang[$value] ?? '?') : '?';
    $side = ['front' => '贵前', 'rear' => '贵后', 'center' => '贵人居中'];
    $gods = is_array($trace['four_gods'] ?? null) ? $trace['four_gods'] : [];
    $pattern = $trace['pattern'] ?? null;
    $patternLabel = $trace['pattern_label'] ?? '分型资料不足';
@endphp

<section class="pan-block mt-4 overflow-hidden bg-base-200/45" aria-label="励德判断过程">
    <div class="px-4 py-4 sm:px-5">
        <h3 class="font-semibold">励德判断</h3>

        <div class="mt-4 pan-block bg-primary/10 px-4 py-4 ring-1 ring-primary/25">
            <div class="flex items-center justify-between gap-3">
                <strong>课级 matcher：贵人临卯酉</strong>
                <span class="text-sm font-medium text-primary">成立</span>
            </div>
            <p class="mt-2 text-sm leading-6 text-base-content/65">
                天乙贵人临地盘{{ $branch($trace['nobleman_ground'] ?? null) }}，属于卯、酉日月门之一。
            </p>
            <p class="mt-1 text-sm font-medium text-primary">→ 仅凭此条件，励德课成立。</p>
        </div>

        <div class="mt-4">
            <h4 class="text-sm font-semibold tracking-wide text-base-content/70">四课分型（不参与成课）</h4>
            <p class="mt-1 text-sm leading-6 text-base-content/55">前后严格按天将判断：贵前五将、贵人居中、贵后六将；不得把四种完整分型反向加入课级 matcher。</p>

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
                    <strong>完整分型：{{ $patternLabel }}</strong>
                    @if ($pattern === 'mixed')
                        <p class="mt-1 text-base-content/60">本盘未落入微服、蹉跎、阳前阴后、阴前阳后四种完整分型，仍然是励德课。</p>
                    @elseif ($pattern === 'weifu' || $pattern === 'cuotuo')
                        <p class="mt-1 text-base-content/60">该格另由独立格规则命中，不改变励德课级成立条件。</p>
                    @endif
                </div>
            @else
                <p class="mt-3 text-sm text-base-content/55">四课资料不足，未进行前后分型；贵人临卯酉的课级判断不受影响。</p>
            @endif
        </div>

        @if (! empty($trace['judgments']))
            <div class="mt-4 border-t border-base-300/70 pt-4">
                <h4 class="text-sm font-semibold tracking-wide text-base-content/70">吉凶判断</h4>
                <div class="mt-3 space-y-3">
                    @foreach ($trace['judgments'] as $judgment)
                        <div class="border-l-2 border-primary/35 pl-4">
                            <strong>{{ $judgment['label'] }}</strong>
                            <p class="mt-1 text-sm leading-6 text-base-content/60">{{ $judgment['evidence'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</section>
