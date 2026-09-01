@php
    $reasoning = $trace['reasoning'];
    $wangXiang = $reasoning['wang_xiang_elements'];
    $seasonalPeriod = $reasoning['seasonal_period'];
@endphp

<section class="pan-block mt-4 bg-base-200/45 px-4 py-4 sm:px-5" aria-label="三光判断过程">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="font-semibold">三光判断</h3>
            <p class="mt-1 text-sm text-base-content/55">
                {{ $seasonalPeriod['name'] }}：{{ $wuxing[$wangXiang['wang']] }}旺，{{ $wuxing[$wangXiang['xiang']] }}相
            </p>
        </div>
        <x-badge value="三处皆成" class="badge-primary badge-soft" />
    </div>

    <div class="mt-4 grid gap-3 md:grid-cols-3">
        @foreach ($reasoning['positions'] as $position)
            @php
                $subject = $position['subject_type'] === 'stem'
                    ? $tiangan[$position['subject']]
                    : $dizhi[$position['subject']];
                $upper = $dizhi[$position['upper_branch']];
                $general = $tianjiangNames[$position['general']];
            @endphp
            <div class="pan-block bg-base-100/75 px-4 py-4">
                <div class="flex items-center justify-between gap-2">
                    <strong>{{ $position['label'] }}光</strong>
                    <div class="flex gap-1.5">
                        <x-badge :value="$position['strength']" class="badge-warning badge-soft" />
                        <x-badge value="吉将" class="badge-success badge-soft" />
                    </div>
                </div>
                <p class="mt-3 text-sm leading-6 text-base-content/65">
                    {{ $subject }}属{{ $wuxing[$position['element']] }}，得季节<strong>{{ $position['strength'] }}</strong>。
                </p>
                <p class="mt-1 text-sm leading-6 text-base-content/65">
                    @if ($position['label'] === '用')
                        发用{{ $upper }}乘<strong>{{ $general }}</strong>。
                    @else
                        {{ $subject }}上{{ $upper }}乘<strong>{{ $general }}</strong>。
                    @endif
                </p>
            </div>
        @endforeach
    </div>

    <p class="mt-3 text-xs leading-5 text-base-content/45">
        旺相时段：{{ $seasonalPeriod['starts_at'] }} 至 {{ $seasonalPeriod['ends_at'] }}。
        四季末十八日按下一个四立交节时刻前推十八个整日计算。
    </p>

    <p class="mt-4 text-sm leading-6 text-base-content/60">日、辰、用三处均旺相且乘吉将，故成三光课。</p>
</section>
