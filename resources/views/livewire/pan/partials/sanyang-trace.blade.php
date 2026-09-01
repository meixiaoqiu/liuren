@php
    $wangXiang = $trace['wang_xiang_elements'];
    $day = $trace['day_stem'];
    $branch = $trace['day_branch'];
    $initial = $trace['initial_transmission'];
@endphp

<section class="pan-block mt-4 bg-base-200/45 px-4 py-4 sm:px-5" aria-label="三阳判断过程">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="font-semibold">三阳判断</h3>
            <p class="mt-1 text-sm text-base-content/55">季节旺相：{{ $wuxing[$wangXiang['wang']] }}旺，{{ $wuxing[$wangXiang['xiang']] }}相</p>
        </div>
        <x-badge value="三阳开泰" class="badge-primary badge-soft" />
    </div>

    <div class="mt-4 grid gap-3 md:grid-cols-3">
        <div class="pan-block bg-base-100/75 px-4 py-4">
            <div class="flex items-center justify-between gap-2">
                <strong>贵人顺行</strong>
                <x-badge value="阳气顺" class="badge-success badge-soft" />
            </div>
            <p class="mt-3 text-sm leading-6 text-base-content/65">
                贵人临{{ $dizhi[$trace['nobleman_ground']] }}，十二天将顺行。
            </p>
        </div>

        <div class="pan-block bg-base-100/75 px-4 py-4">
            <div class="flex items-center justify-between gap-2">
                <strong>日辰居前</strong>
                <x-badge value="阳气伸" class="badge-success badge-soft" />
            </div>
            <p class="mt-3 text-sm leading-6 text-base-content/65">
                {{ $tiangan[$day['stem']] }}寄{{ $dizhi[$day['lodging_branch']] }}，乘{{ $tianjiangNames[$day['general']] }}，为贵前第{{ $day['front_general_rank'] }}将。
            </p>
            <p class="mt-1 text-sm leading-6 text-base-content/65">
                日支{{ $dizhi[$branch['branch']] }}乘{{ $tianjiangNames[$branch['general']] }}，为贵前第{{ $branch['front_general_rank'] }}将。
            </p>
        </div>

        <div class="pan-block bg-base-100/75 px-4 py-4">
            <div class="flex items-center justify-between gap-2">
                <strong>发用旺相</strong>
                <x-badge value="阳气进" class="badge-success badge-soft" />
            </div>
            <p class="mt-3 text-sm leading-6 text-base-content/65">
                发用{{ $dizhi[$initial['branch']] }}属{{ $wuxing[$initial['element']] }}为{{ $initial['strength'] }}。
            </p>
        </div>
    </div>

    <p class="mt-4 text-sm leading-6 text-base-content/60">贵人顺行，日辰均乘贵前五将，发用又得季节旺相，故成三阳课。</p>
</section>
