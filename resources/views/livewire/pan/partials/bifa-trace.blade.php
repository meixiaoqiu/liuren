@php
    $bifa = $bifa ?? null;
    $relatedCases = $bifa['related_cases'] ?? [];
@endphp

@if ($bifa !== null)
    <article class="pan-block bg-base-100/70 px-5 py-4">
        <div class="flex flex-wrap items-center gap-3">
            <span class="grid h-9 w-9 shrink-0 place-items-center bg-primary text-sm font-semibold text-primary-content">
                毕
            </span>
            <div>
                <span class="text-xs text-base-content/45">第 {{ $bifa['number'] }} 法 · 毕法体系</span>
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
                        @elseif ($sub['needs_people'])
                            <x-badge value="待评估" class="badge-warning badge-sm" />
                        @else
                            <x-badge value="○ 不成立" class="badge-ghost badge-sm" />
                        @endif
                        <strong class="text-sm">{{ $sub['title'] }}</strong>
                    </div>
                    <p class="mt-1 text-sm leading-6 text-base-content/55">{{ $sub['description'] }}</p>

                    @if (! empty($sub['detail']))
                        <p class="mt-2 text-sm leading-6 text-base-content/70">{{ $sub['detail'] }}</p>
                    @elseif ($sub['needs_people'])
                        <p class="mt-2 text-sm leading-6 text-warning-content/80">
                            本分格需要占测者本命 / 行年资料；当前盘面未提供本命 / 行年，已标记为待评估。
                        </p>
                    @endif
                </div>
            @endforeach
        </div>

        @if ($bifa['matched'])
            <p class="mt-4 text-sm font-medium text-success">
                ✓ 本法成立（{{ $bifa['matched_count'] }} 个分格命中）
            </p>
        @else
            <p class="mt-4 text-sm text-base-content/50">本法当前未命中。</p>
        @endif

        @if (! empty($relatedCases))
            <div class="mt-5 border-t border-base-300/70 pt-4">
                <h3 class="text-sm font-medium text-base-content/80">相关案例</h3>
                <p class="mt-1 text-xs text-base-content/45">仅展示与当前命中分格相关的案例。</p>
                <div class="mt-3 space-y-3">
                    @foreach ($relatedCases as $case)
                        @php
                            $href = $case['url'];
                        @endphp
                        <div class="rounded-lg border border-base-300/70 bg-base-100 px-4 py-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <x-badge :value="$case['source_label']" class="badge-primary badge-soft badge-sm" />
                                <x-badge :value="$case['status_label']" class="badge-{{ $case['status_tone'] }} badge-soft badge-sm" />
                                <strong class="text-sm">{{ $case['label'] }}</strong>
                            </div>
                            <p class="mt-2 text-sm leading-6 text-base-content/60">{{ $case['description'] }}</p>
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                @if ($href !== null)
                                    <x-button
                                        label="查看排盘 →"
                                        :link="$href"
                                        class="btn-ghost btn-xs"
                                    />
                                @else
                                    <span class="text-xs text-base-content/45">原文参考盘 · 尚未完整复现</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </article>
@endif
