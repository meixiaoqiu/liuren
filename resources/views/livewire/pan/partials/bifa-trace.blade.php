@php
    $bifa = $bifa ?? null;
@endphp

@if ($bifa !== null)
    <article class="pan-block bg-base-100/70 px-5 py-4">
        <div class="flex flex-wrap items-center gap-3">
            <span class="grid h-9 w-9 shrink-0 place-items-center bg-primary text-sm font-semibold text-primary-content">
                毕
            </span>
            <div>
                <span class="text-xs text-base-content/45">第 {{ $bifa['code'] ?? '' }} 法 · 毕法体系</span>
                <h2 class="text-lg font-semibold">{{ $bifa['name'] }}</h2>
            </div>
        </div>

        @if (! empty($bifa['summary']))
            <p class="mt-2 leading-7 text-base-content/65">{{ $bifa['summary'] }}</p>
        @endif

        <div class="mt-4 space-y-2">
            @foreach ($bifa['sub_matches'] ?? [] as $sub)
                <div class="rounded-lg border border-base-300/70 bg-base-100 px-4 py-3">
                    <div class="flex flex-wrap items-baseline gap-2">
                        @if ($sub['matched'])
                            <x-badge value="✓ 命中" class="badge-success badge-sm" />
                        @elseif (! empty($sub['people_missing']) && ! empty($sub['requires_people']))
                            <x-badge value="待评估" class="badge-warning badge-sm" />
                        @else
                            <x-badge value="○ 不成立" class="badge-ghost badge-sm" />
                        @endif
                        <strong class="text-sm">{{ $sub['title'] }}</strong>
                    </div>
                    <p class="mt-1 text-sm leading-6 text-base-content/55">{{ $sub['description'] }}</p>

                    @if (! empty($sub['detail']))
                        <p class="mt-2 text-sm leading-6 text-base-content/70">{{ $sub['detail'] }}</p>
                    @elseif (! empty($sub['people_missing']) && ! empty($sub['requires_people']))
                        <p class="mt-2 text-sm leading-6 text-warning-content/80">
                            本分格需要占测者本命 / 行年资料；当前盘面缺少人物输入，已标记为未评估。
                        </p>
                    @endif
                </div>
            @endforeach
        </div>

        @if ($bifa['matched'])
            <p class="mt-4 text-sm font-medium text-success">
                ✓ 本法成立（{{ count($bifa['matched_routes']) }} 个分格命中）
            </p>
        @else
            <p class="mt-4 text-sm text-base-content/50">本法当前未成立。</p>
        @endif
    </article>
@endif