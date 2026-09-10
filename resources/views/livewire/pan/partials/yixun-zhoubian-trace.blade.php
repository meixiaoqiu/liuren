@php
    $branches = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];
    $stems = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];
@endphp

<section class="pan-block mt-4 overflow-hidden bg-base-200/45" aria-label="一旬周遍格依据">
    <div class="px-4 py-4 sm:px-5">
        <h4 class="text-sm font-semibold tracking-wide text-base-content/70">一旬周遍格依据</h4>
        <p class="mt-3 text-sm leading-6 text-base-content/65">
            {{ $stems[$trace['day_stem']] ?? '?' }}日寄宫{{ $branches[$trace['stem_lodging']] ?? '?' }}，
            宫上见旬尾{{ $branches[$trace['stem_upper']] ?? '?' }}；
            日支{{ $branches[$trace['day_branch']] ?? '?' }}上见旬首{{ $branches[$trace['branch_upper']] ?? '?' }}。
            旬尾加干、旬首加支，故独立成立一旬周遍格。
        </p>
        <p class="mt-2 text-xs leading-5 text-base-content/50">本格载于《六壬大全》闭口课篇，但不要求闭口课先成立，也不反向补出闭口课。</p>
    </div>
</section>
