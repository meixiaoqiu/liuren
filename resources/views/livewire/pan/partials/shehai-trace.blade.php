@if (! empty($pan['shehaiTrace']))
    <section class="overflow-hidden rounded-3xl bg-base-200/45" aria-labelledby="shehai-process-heading">
        <div class="flex items-start gap-3 px-5 pb-4 pt-5 sm:px-6 sm:pt-6">
            <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-primary text-sm font-semibold text-primary-content shadow-sm">涉</span>
            <div>
                <h2 id="shehai-process-heading" class="text-lg font-semibold">涉害过程</h2>
                <p class="mt-1 text-sm text-base-content/55">逐课比较涉历归本途中所遇克贼</p>
            </div>
        </div>

        <div class="space-y-5 px-4 pb-4 sm:px-6 sm:pb-6">
            <div class="flex flex-wrap items-center gap-2 rounded-xl bg-base-100/70 px-4 py-3 text-sm text-base-content/65">
                <span>本课按</span>
                <x-badge :value="$pan['shehaiTrace']['relation']" class="badge-warning badge-soft" />
                <span>方向计算涉害深浅。</span>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                @foreach ($pan['shehaiTrace']['candidates'] as $candidate)
                    @php
                        $isSelected = $candidate['lesson_index'] === $pan['shehaiTrace']['decision']['selected_lesson_index'];
                        $lowerName = $candidate['lower_type'] === 'stem'
                            ? $tiangan[$candidate['lower']].'（寄'.$dizhi[$candidate['lower_ground']].'宫）'
                            : $dizhi[$candidate['lower']];
                    @endphp
                    <article class="relative rounded-2xl bg-base-100 p-5 shadow-sm ring-1 {{ $isSelected ? 'ring-primary/35' : 'ring-base-content/8' }} sm:p-6">
                        @if ($isSelected)
                            <span class="absolute inset-y-5 left-0 w-1 rounded-r-full bg-primary" aria-hidden="true"></span>
                        @endif
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h3 class="font-semibold">第{{ $candidate['lesson'] }}课候选</h3>
                                <p class="mt-1 text-sm text-base-content/60">
                                    下神 {{ $lowerName }} · 上神 {{ $dizhi[$candidate['upper']] }}
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-2">
                                @if ($isSelected)
                                    <x-badge value="取用" class="badge-primary badge-soft" />
                                @endif
                                <x-badge value="{{ $candidate['depth'] }}重" class="badge-neutral badge-soft" />
                            </div>
                        </div>

                        <div class="mt-5 border-t border-base-content/8 pt-4">
                            <p class="text-xs font-medium tracking-wide text-base-content/45">涉历路径</p>
                            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                @foreach ($candidate['path'] as $pathIndex => $ground)
                                    @if ($pathIndex > 0)
                                        <span class="text-base-content/30">→</span>
                                    @endif
                                    <span class="grid size-8 place-items-center rounded-full bg-base-200 text-sm font-medium">{{ $dizhi[$ground] }}</span>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-5 border-t border-base-content/8 pt-4">
                            <p class="text-xs font-medium tracking-wide text-base-content/45">克贼明细</p>
                            @if ($candidate['encounters'] === [])
                                <p class="mt-2 text-sm text-base-content/55">途中未遇克贼。</p>
                            @else
                                <ol class="mt-3 space-y-2.5">
                                    @foreach ($candidate['encounters'] as $encounterIndex => $encounter)
                                        @php
                                            $sourceName = $encounter['source_kind'] === 'stem'
                                                ? $tiangan[$encounter['source']]
                                                : $dizhi[$encounter['source']];
                                            $targetName = $encounter['target_kind'] === 'stem'
                                                ? $tiangan[$encounter['target']]
                                                : $dizhi[$encounter['target']];
                                        @endphp
                                        <li class="flex items-start gap-3 rounded-xl bg-base-200/65 px-4 py-3 text-sm leading-6">
                                            <span class="grid size-6 shrink-0 place-items-center rounded-full bg-base-100 text-xs font-medium text-base-content/50 shadow-xs">{{ $encounterIndex + 1 }}</span>
                                            <span>地盘{{ $dizhi[$encounter['ground']] }}位：{{ $sourceName }}{{ $encounter['relation'] }}{{ $targetName }}</span>
                                        </li>
                                    @endforeach
                                </ol>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="flex items-start gap-4 rounded-2xl bg-primary px-5 py-5 text-primary-content shadow-sm sm:px-6">
                <span class="grid size-10 shrink-0 place-items-center rounded-full bg-primary-content/15 text-lg font-semibold">
                    {{ $dizhi[$pan['shehaiTrace']['decision']['selected_branch']] }}
                </span>
                <div>
                    <p class="text-xs font-medium tracking-wider text-primary-content/65">最终取用</p>
                    <p class="mt-1.5 leading-7">
                        {{ $pan['shehaiTrace']['decision']['rule'] }}，取
                        <strong class="font-semibold">{{ $dizhi[$pan['shehaiTrace']['decision']['selected_branch']] }}</strong>
                        为初传。
                    </p>
                </div>
            </div>
        </div>
    </section>
@endif
