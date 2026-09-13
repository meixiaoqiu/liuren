@php($branch = $dizhi[$trace['matched_branch']])
<section class="pan-block mt-4 bg-base-200/45 px-4 py-4 sm:px-5" aria-label="龙战判断过程">
    <h3 class="font-semibold">龙战判断</h3>
    <div class="mt-4 grid gap-3 md:grid-cols-3">
        <div class="pan-block bg-base-100/75 px-4 py-4"><strong>当前日支</strong><p class="mt-2 text-sm">{{ $dizhi[$trace['day_branch']] }}</p></div>
        <div class="pan-block bg-base-100/75 px-4 py-4"><strong>当前初传</strong><p class="mt-2 text-sm">{{ $dizhi[$trace['initial']] }}</p></div>
        <div class="pan-block bg-base-100/75 px-4 py-4"><strong>当前占者行年</strong><p class="mt-2 text-sm">{{ $dizhi[$trace['querent_xingnian']] }}</p></div>
    </div>
    <p class="mt-4 text-sm leading-6 text-base-content/70">三者同位于{{ $branch }}，龙战课成立。</p>
    @if (! empty($trace['judgments']))
        <div class="mt-4"><strong class="text-sm">附加判断</strong><ul class="mt-2 space-y-2">@foreach ($trace['judgments'] as $judgment)<li class="text-sm"><span class="font-medium">{{ $judgment['title'] }}</span>：{{ $judgment['detail'] }}</li>@endforeach</ul></div>
    @endif
    @if (! empty($trace['uncovered']))
        <details class="mt-4 text-sm text-base-content/55"><summary class="cursor-pointer">异说与尚未覆盖项</summary><ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($trace['uncovered'] as $item)<li>{{ $item }}</li>@endforeach</ul></details>
    @endif
</section>
