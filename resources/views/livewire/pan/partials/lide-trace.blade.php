@php
    $branch = fn ($value) => is_int($value) ? (\App\Services\PanCalculator::$dizhi[$value] ?? '?') : '?';
    $general = fn ($value) => is_int($value) ? (\App\Services\PanCalculator::$tianjiang[$value] ?? '?') : '?';
    $side = ['front' => '贵前', 'rear' => '贵后', 'center' => '贵人居中'];
    $gods = is_array($trace['four_gods'] ?? null) ? $trace['four_gods'] : [];
    $pattern = $trace['pattern'] ?? null;
    $patternLabel = $trace['pattern_label'] ?? '分型资料不足';
    $hasJudgments = ! empty($trace['judgments'] ?? []);
    $effectMeta = [
        'increase' => ['label' => '增强', 'class' => 'badge-success badge-soft'],
        'reduce' => ['label' => '减损', 'class' => 'badge-warning badge-soft'],
        'resolve' => ['label' => '例外', 'class' => 'badge-info badge-soft'],
        'neutral' => ['label' => '中性', 'class' => 'badge-ghost'],
    ];
@endphp

<section class="pan-block mt-4 overflow-hidden bg-base-200/45" aria-label="励德判断过程">
    <h3 class="px-4 pt-4 font-semibold sm:px-5">励德判断</h3>

    <div class="mt-4 grid {{ $hasJudgments ? 'lg:grid-cols-2' : '' }}">
        <div class="px-4 py-4 sm:px-5 {{ $hasJudgments ? 'lg:border-r lg:border-base-300/70' : '' }}">
            <h4 class="text-sm font-semibold tracking-wide text-base-content/70">成课条件</h4>
            <ol class="mt-4 space-y-4">
                <li class="grid grid-cols-[1.75rem_minmax(0,1fr)] gap-2">
                    <span class="grid size-7 place-items-center rounded-full bg-primary/12 text-xs font-semibold text-primary">1</span>
                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <strong>天乙贵人临卯酉</strong>
                            <span class="text-sm font-medium text-primary">成立</span>
                        </div>
                        <p class="mt-1 text-sm leading-6 text-base-content/65">
                            天乙贵人临地盘{{ $branch($trace['nobleman_ground'] ?? null) }}，符合卯、酉之一。
                        </p>
                    </div>
                </li>
            </ol>
            <p class="mt-4 text-sm font-medium text-primary">→ 励德课成立</p>
        </div>

        @if ($hasJudgments)
            <div class="border-t border-base-300/70 px-4 py-4 sm:px-5 lg:border-t-0">
                <h4 class="text-sm font-semibold tracking-wide text-base-content/70">吉凶判断</h4>
                <div class="mt-3 space-y-3">
                    @foreach ($trace['judgments'] as $judgment)
                        <div class="border-l-2 border-primary/35 pl-4">
                            @if (isset($judgment['effect'], $effectMeta[$judgment['effect']]))
                                <x-badge :value="$effectMeta[$judgment['effect']]['label']" class="{{ $effectMeta[$judgment['effect']]['class'] }}" />
                            @endif
                            <strong class="mt-2 block">{{ $judgment['label'] }}</strong>
                            <p class="mt-1 text-sm leading-6 text-base-content/60">{{ $judgment['evidence'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

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
