@php
    $branch = fn ($value) => is_int($value) ? (\App\Services\PanCalculator::$dizhi[$value] ?? '?') : '?';
@endphp
<section class="pan-block mt-4 bg-base-200/45 px-4 py-4 sm:px-5" aria-label="九丑判断过程">
    <h3 class="font-semibold">九丑判断</h3>
    <div class="mt-3 space-y-1 text-sm leading-6 text-base-content/70">
        <p>九丑日：{{ $trace['day_ganzhi'] ?? '?' }}</p>
        <p>丑所临地盘：{{ $branch($trace['chou_ground'] ?? null) }}</p>
        <p>日支：{{ $branch($trace['day_branch'] ?? null) }}</p>
        <p class="font-medium text-primary">→ 丑临日支，主体成课</p>
    </div>
    <div class="mt-4 pan-block bg-base-100/75 px-4 py-3 text-sm leading-6">
        <p>四仲时：{{ ($trace['four_zhong_time'] ?? false) ? '是' : '否' }}</p>
        <p>丑发用：{{ ($trace['chou_fayong'] ?? false) ? '是' : '否' }}</p>
        <p class="font-medium">《大全》正文严格形态：{{ ($trace['strict_daquan_form'] ?? false) ? '是（完全符合《六壬大全》正文严格形态）' : '否' }}</p>
    </div>
    @if (! empty($trace['uncovered']))
        <details class="mt-4 text-sm text-base-content/55"><summary class="cursor-pointer">尚未程序化项</summary>
            <ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($trace['uncovered'] as $item)<li>{{ $item }}</li>@endforeach</ul>
        </details>
    @endif
</section>
