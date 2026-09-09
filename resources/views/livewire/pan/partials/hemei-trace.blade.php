@php
    $branches = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];
    $positions = ['初传', '中传', '末传'];
    $g = $trace['day_stem_lodging_branch']; $z = $trace['day_branch'];
    $gu = $trace['day_upper']; $zu = $trace['branch_upper'];
    $detail = $trace['sanhe_transmission_detail'] ?? null;
@endphp
<section class="pan-block mt-4 bg-base-200/45 px-4 py-4 sm:px-5" aria-label="和美判断过程">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div><h3 class="font-semibold">和美判断（正文三路结构）</h3>
            <p class="mt-1 text-sm text-base-content/55">日干寄宫{{ $branches[$g] }}、干上神{{ $branches[$gu] }}；日支{{ $branches[$z] }}、支上神{{ $branches[$zu] }}；三传{{ $branches[$trace['initial']] }}、{{ $branches[$trace['middle']] }}、{{ $branches[$trace['final']] }}。</p>
        </div><x-badge value="和美成立" class="badge-primary badge-soft" />
    </div>
    <div class="mt-4 grid gap-3 md:grid-cols-3">
        <div class="pan-block bg-base-100/75 px-4 py-4"><strong>一、干支交互作六合</strong><p class="mt-3 text-sm leading-6 text-base-content/65">日干寄宫{{ $branches[$g] }}与支上神{{ $branches[$zu] }}，并且日支{{ $branches[$z] }}与干上神{{ $branches[$gu] }}：<strong>{{ $trace['cross_liuhe'] ? '成立' : '不成立' }}</strong>。</p></div>
        <div class="pan-block bg-base-100/75 px-4 py-4"><strong>二、干支各与本位上神作六合</strong><p class="mt-3 text-sm leading-6 text-base-content/65">日干寄宫{{ $branches[$g] }}与干上神{{ $branches[$gu] }}，并且日支{{ $branches[$z] }}与支上神{{ $branches[$zu] }}：<strong>{{ $trace['same_liuhe'] ? '成立' : '不成立' }}</strong>。</p></div>
        <div class="pan-block bg-base-100/75 px-4 py-4"><strong>三、干支上神与一传作三合</strong><p class="mt-3 text-sm leading-6 text-base-content/65">干上神{{ $branches[$gu] }}、支上神{{ $branches[$zu] }}与三传中的一支：<strong>{{ $trace['upper_sanhe_transmission'] ? '成立' : '不成立' }}</strong>。@if (is_array($detail)) 命中{{ $positions[$detail['position']] }}{{ $branches[$detail['branch']] }}。@endif</p></div>
    </div>
    @if (! empty($trace['uncovered']))<details class="mt-4 text-sm text-base-content/55"><summary class="cursor-pointer">尚未纳入主体判断的异说与课义</summary><ul class="mt-2 list-disc pl-5">@foreach ($trace['uncovered'] as $item)<li>{{ $item }}</li>@endforeach</ul></details>@endif
</section>
