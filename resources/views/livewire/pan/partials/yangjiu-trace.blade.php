@php
    $routes = $trace['routes'] ?? [];
    $groups = [
        '递克' => ['forward_recursive_overcoming', 'reverse_recursive_overcoming'],
        '初传夹克' => ['initial_transmission_sandwiched_overcoming'],
        '三传外战' => ['all_three_external_battle'],
        '三传内战' => ['all_three_internal_battle'],
        '干支乘墓' => ['stem_branch_riding_tombs'],
        '干支坐墓' => ['stem_branch_sitting_on_tombs'],
    ];
@endphp
<section class="pan-block mt-4 bg-base-200/45 px-4 py-4 sm:px-5" aria-label="殃咎判断过程">
    <h3 class="font-semibold">殃咎判断</h3>
    <p class="mt-2 text-sm leading-6 text-base-content/60">七条路线任一成立即成课；同盘命中的路线全部保留。</p>
    <div class="mt-4 grid gap-3 md:grid-cols-2">
        @foreach ($groups as $groupName => $keys)
            @php $groupMatched = collect($keys)->contains(fn ($key) => ($routes[$key]['matched'] ?? false) === true); @endphp
            <div class="pan-block px-4 py-4 {{ $groupMatched ? 'bg-primary/10 ring-1 ring-primary/25' : 'bg-base-100/75' }}">
                <div class="flex items-center justify-between gap-3">
                    <strong>{{ $groupName }}</strong>
                    <span class="text-sm font-medium {{ $groupMatched ? 'text-primary' : 'text-base-content/45' }}">{{ $groupMatched ? '成立' : '不成立' }}</span>
                </div>
                @foreach ($keys as $key)
                    @php $route = $routes[$key] ?? null; @endphp
                    @if ($route !== null)
                        @if (count($keys) > 1)
                            <p class="mt-3 text-sm font-medium">{{ $route['label'] }}：{{ $route['matched'] ? '成立' : '不成立' }}</p>
                        @endif
                        <ul class="mt-2 space-y-1 text-sm leading-6 text-base-content/65">
                            @foreach ($route['details'] as $detail)
                                <li>{{ $detail }}</li>
                            @endforeach
                        </ul>
                    @endif
                @endforeach
            </div>
        @endforeach
    </div>
    @if (! empty($trace['uncovered']))
        <details class="mt-4 text-sm text-base-content/55">
            <summary class="cursor-pointer">吉凶判断、异说与尚未程序化项</summary>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($trace['uncovered'] as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </details>
    @endif
</section>
