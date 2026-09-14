@php
    $branch = fn ($value) => is_int($value) ? (\App\Services\PanCalculator::$dizhi[$value] ?? '?') : '?';
    $stem = fn ($value) => is_int($value) ? (\App\Services\PanCalculator::$tiangan[$value] ?? '?') : '?';
    $element = fn ($value) => is_int($value) ? (\App\Services\PanCalculator::$wuxing[$value] ?? '?') : '?';
    $dayGhosts = $trace['day_ghosts'] ?? [];
    $matched = $trace['matched_routes'] ?? [];
@endphp
<section class="pan-block mt-4 bg-base-200/45 px-4 py-4 sm:px-5" aria-label="鬼墓判断过程">
    <h3 class="font-semibold">鬼墓判断</h3>
    <p class="mt-2 text-sm leading-6 text-base-content/60">三条入口「日鬼 / 日干墓 / 日支墓」并列 OR；初传同时是鬼与墓时显示「鬼墓兼见（鬼墓俱见）」。</p>
    <div class="mt-4 grid gap-3 md:grid-cols-2">
        <div class="pan-block px-4 py-4 {{ ($trace['day_ghost_fayong'] ?? false) ? 'bg-primary/10 ring-1 ring-primary/25' : 'bg-base-100/75' }}">
            <div class="flex items-center justify-between gap-3">
                <strong>日鬼发用</strong>
                <span class="text-sm font-medium {{ ($trace['day_ghost_fayong'] ?? false) ? 'text-primary' : 'text-base-content/45' }}">{{ ($trace['day_ghost_fayong'] ?? false) ? '成立' : '不成立' }}</span>
            </div>
            <p class="mt-2 text-sm leading-6 text-base-content/65">
                日干 {{ $stem($trace['day_stem'] ?? null) }}，日鬼集合 {{ implode('、', array_map($branch, $dayGhosts)) }}；初传 {{ $branch($trace['initial'] ?? null) }}。
            </p>
        </div>
        <div class="pan-block px-4 py-4 {{ ($trace['stem_tomb_fayong'] ?? false) ? 'bg-primary/10 ring-1 ring-primary/25' : 'bg-base-100/75' }}">
            <div class="flex items-center justify-between gap-3">
                <strong>日干墓发用</strong>
                <span class="text-sm font-medium {{ ($trace['stem_tomb_fayong'] ?? false) ? 'text-primary' : 'text-base-content/45' }}">{{ ($trace['stem_tomb_fayong'] ?? false) ? '成立' : '不成立' }}</span>
            </div>
            <p class="mt-2 text-sm leading-6 text-base-content/65">
                日干 {{ $stem($trace['day_stem'] ?? null) }} 属 {{ $element($trace['stem_element'] ?? null) }}，五行墓在 {{ $branch($trace['stem_tomb'] ?? null) }}；初传 {{ $branch($trace['initial'] ?? null) }}。
            </p>
        </div>
        <div class="pan-block px-4 py-4 {{ ($trace['branch_tomb_fayong'] ?? false) ? 'bg-primary/10 ring-1 ring-primary/25' : 'bg-base-100/75' }}">
            <div class="flex items-center justify-between gap-3">
                <strong>日支墓发用</strong>
                <span class="text-sm font-medium {{ ($trace['branch_tomb_fayong'] ?? false) ? 'text-primary' : 'text-base-content/45' }}">{{ ($trace['branch_tomb_fayong'] ?? false) ? '成立' : '不成立' }}</span>
            </div>
            <p class="mt-2 text-sm leading-6 text-base-content/65">
                日支 {{ $branch($trace['day_branch'] ?? null) }} 属 {{ $element($trace['branch_element'] ?? null) }}，五行墓在 {{ $branch($trace['branch_tomb'] ?? null) }}；初传 {{ $branch($trace['initial'] ?? null) }}。
            </p>
        </div>
        <div class="pan-block px-4 py-4 bg-base-100/75">
            <div class="flex items-center justify-between gap-3">
                <strong>鬼墓兼见（鬼墓俱见）</strong>
                <span class="text-sm font-medium {{ ($trace['ghost_tomb_combined'] ?? false) ? 'text-primary' : 'text-base-content/45' }}">{{ ($trace['ghost_tomb_combined'] ?? false) ? '成立' : '不成立' }}</span>
            </div>
            <p class="mt-2 text-sm leading-6 text-base-content/65">初传同时是日鬼与日干墓或日支墓时成立；不成立不影响鬼墓课主体。</p>
        </div>
    </div>
    <div class="mt-4 pan-block bg-base-100/75 px-4 py-3 text-sm leading-6">
        <p>命中路线：{{ $matched === [] ? '（无）' : implode('、', $matched) }}</p>
        <p class="font-medium text-primary">→ {{ $matched === [] ? '三条入口均不满足，鬼墓课不成立' : '鬼墓课成立' }}</p>
    </div>
    @if (! empty($trace['judgments']))
        <div class="mt-4 pan-block bg-base-100/75 px-4 py-3 text-sm leading-6">
            <p class="font-semibold">增强判断</p>
            <ul class="mt-2 space-y-1">
                @foreach ($trace['judgments'] as $judgment)
                    <li>
                        <span class="font-medium">{{ $judgment['label'] ?? $judgment['code'] }}</span>：{{ $judgment['evidence'] ?? '' }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
    @if (! empty($trace['uncovered']))
        <details class="mt-4 text-sm text-base-content/55"><summary class="cursor-pointer">尚未程序化项</summary>
            <ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($trace['uncovered'] as $item)<li>{{ $item }}</li>@endforeach</ul>
        </details>
    @endif
</section>
