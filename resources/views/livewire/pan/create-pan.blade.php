<div class="min-h-screen">
    <header class="border-b border-base-300 bg-base-100/90 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center px-4 py-4 sm:px-6 lg:px-8">
            <div class="flex items-center gap-3">
                <div class="grid size-10 place-items-center rounded-xl bg-primary text-lg font-semibold text-primary-content shadow-sm">壬</div>
                <div>
                    <p class="text-base font-semibold tracking-wide">大六壬排盘</p>
                    <p class="text-xs text-base-content/55">以时起课 · 北京时间</p>
                </div>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-0 py-4 sm:px-6 sm:py-8 lg:px-8 lg:py-12">
        <div class="grid min-w-0 gap-4 lg:grid-cols-[22rem_minmax(0,1fr)] lg:items-start lg:gap-6">
            <aside class="lg:sticky lg:top-6">
                <x-card title="选择起课时间" subtitle="只需输入时间，其余参数由系统自动推算。" class="pan-mobile-edge" shadow separator>
                    <x-form wire:submit="calculate">
                        @csrf
                        <x-datetime
                            label="北京时间"
                            wire:model="datetime"
                            type="datetime-local"
                            icon="o-calendar-days"
                            hint="系统按 Asia/Shanghai 时区计算"
                            required
                        />

                        <x-slot:actions>
                            <x-button label="立即排盘" type="submit" icon="o-sparkles" class="btn-primary w-full" spinner="calculate" />
                        </x-slot:actions>
                    </x-form>
                </x-card>
            </aside>

            <section class="min-w-0" aria-live="polite">
                @if ($pan === null)
                    <x-card class="pan-mobile-edge min-h-96 border border-dashed border-base-300 bg-base-100/60">
                        <div class="grid min-h-80 place-items-center text-center">
                            <div class="max-w-sm">
                                <div class="mx-auto mb-5 grid size-16 place-items-center rounded-2xl bg-primary/10 text-2xl text-primary">课</div>
                                <h1 class="text-2xl font-semibold">开始一次排盘</h1>
                                <p class="mt-3 leading-7 text-base-content/60">选择起课时间并点击“立即排盘”，三传、四课与天地盘会在这里完整呈现。</p>
                            </div>
                        </div>
                    </x-card>
                @else
                    @php
                        $transmissions = [
                            ['name' => '初传', 'index' => 0],
                            ['name' => '中传', 'index' => 1],
                            ['name' => '末传', 'index' => 2],
                        ];
                        $lessonColumns = [
                            ['number' => 4, 'upper' => 7, 'relation' => 3, 'lowerType' => 'branch'],
                            ['number' => 3, 'upper' => 5, 'relation' => 2, 'lowerType' => 'branch'],
                            ['number' => 2, 'upper' => 3, 'relation' => 1, 'lowerType' => 'branch'],
                            ['number' => 1, 'upper' => 1, 'relation' => 0, 'lowerType' => 'stem'],
                        ];
                        $palacePositions = [
                            5 => '1 / 1', 6 => '1 / 2', 7 => '1 / 3', 8 => '1 / 4',
                            4 => '2 / 1', 9 => '2 / 4', 3 => '3 / 1', 10 => '3 / 4',
                            2 => '4 / 1', 1 => '4 / 2', 0 => '4 / 3', 11 => '4 / 4',
                        ];
                    @endphp

                    <div class="pan-result-stack space-y-3 sm:space-y-6">
                        <x-card class="pan-data-card pan-mobile-edge" shadow>
                            <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h1 id="pan-result-heading" class="text-2xl font-semibold tracking-wide sm:text-3xl">{{ $pan['sizhu'] }}</h1>
                                    <p class="mt-2 text-sm text-base-content/55">{{ str_replace('T', ' ', $datetime) }} · 北京时间</p>
                                </div>
                                <div class="rounded-2xl border border-amber-600/20 bg-amber-50 px-5 py-3 text-center">
                                    <p class="text-xs tracking-widest text-amber-800/60">月将</p>
                                    <p class="mt-1 text-2xl font-semibold text-amber-700">{{ $dizhi[$pan['yuejiang']] }}</p>
                                </div>
                            </div>
                        </x-card>

                        <div class="grid gap-6 xl:grid-cols-2">
                            <x-card title="三传" class="pan-data-card pan-mobile-edge" shadow separator>
                                <div class="divide-y divide-base-200">
                                    @foreach ($transmissions as $transmission)
                                        @php
                                            $index = $transmission['index'];
                                            $branch = $pan['sanchuan'.$index];
                                        @endphp
                                        <div class="grid grid-cols-[4rem_minmax(0,1fr)] items-center gap-3 py-4 first:pt-1 last:pb-1">
                                            <span class="text-center text-sm font-medium text-base-content/55">{{ $liuqinNames[$pan['liuqin'.$index]] }}</span>
                                            <div class="text-center">
                                                <x-badge :value="$tianjiangNames[$pan['sanchuan'.$index.'tianjiang']]" class="badge-soft mb-3" />
                                                <p class="mx-auto grid max-w-48 grid-cols-[1fr_auto_1fr] items-baseline gap-1.5">
                                                    <span class="justify-self-end text-xs font-medium text-amber-700">{{ $xundunLabels[$branch] }}</span>
                                                    <span class="text-2xl font-semibold text-primary">{{ $dizhi[$branch] }}</span>
                                                    <span class="justify-self-start text-xs font-medium text-base-content/45">{{ $wuxing[$wuxingDi[$branch]] }}</span>
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </x-card>

                            <x-card title="四课" subtitle="从右至左为一至四课" class="pan-data-card pan-mobile-edge" shadow separator>
                                <div class="grid grid-cols-4 gap-2 text-center">
                                    @foreach ($lessonColumns as $lessonColumn)
                                        @php
                                            $upperIndex = $lessonColumn['upper'];
                                            $relation = $pan['wuxingShengke'.$lessonColumn['relation']];
                                            $relationName = $relation[0] === 0 ? '不生不克' : $relation[1];
                                            $upperGroundIndex = array_search($pan['sike'][$upperIndex], $pan['tianpan'], true);
                                            $lessonTianjiang = $tianjiangNames[$pan['tianjiang'][$upperGroundIndex]];
                                            $lowerTianpanBranch = $lessonColumn['lowerType'] === 'stem'
                                                ? $jigong[$pan['sike'][0]]
                                                : $pan['sike'][$upperIndex - 1];
                                            $lowerGroundIndex = array_search($lowerTianpanBranch, $pan['tianpan'], true);
                                            $lowerTianjiang = $tianjiangNames[$pan['tianjiang'][$lowerGroundIndex]];
                                            $relationClass = match ($relation[0]) {
                                                1, -1 => 'badge-error badge-soft',
                                                2, -2 => 'badge-success badge-soft',
                                                default => 'badge-ghost',
                                            };
                                        @endphp
                                        <div class="rounded-xl bg-base-200 px-2 py-4">
                                            <x-badge
                                                :value="$lessonTianjiang"
                                                class="badge-soft mb-3"
                                                aria-label="第{{ $lessonColumn['number'] }}课天将{{ $lessonTianjiang }}"
                                            />
                                            <p class="grid grid-cols-[1fr_auto_1fr] items-baseline gap-1.5">
                                                <span class="justify-self-end text-xs font-medium text-amber-700">{{ $xundunLabels[$pan['sike'][$upperIndex]] }}</span>
                                                <span class="text-2xl font-semibold text-primary">{{ $dizhi[$pan['sike'][$upperIndex]] }}</span>
                                                <span class="justify-self-start text-xs font-medium text-base-content/45">{{ $wuxing[$wuxingDi[$pan['sike'][$upperIndex]]] }}</span>
                                            </p>
                                            <div class="my-2 flex items-center gap-1">
                                                <span class="h-px min-w-0 flex-1 bg-base-300"></span>
                                                <x-badge :value="$relationName" class="{{ $relationClass }} h-auto px-1.5 py-0.5 text-[0.65rem] whitespace-nowrap" />
                                                <span class="h-px min-w-0 flex-1 bg-base-300"></span>
                                            </div>
                                            <x-badge
                                                :value="$lowerTianjiang"
                                                class="badge-soft mb-3"
                                                aria-label="第{{ $lessonColumn['number'] }}课下层天将{{ $lowerTianjiang }}"
                                            />
                                            <p class="grid grid-cols-[1fr_auto_1fr] items-baseline gap-1.5">
                                                @if ($lessonColumn['lowerType'] === 'stem')
                                                    <span></span>
                                                    <span class="text-2xl font-semibold text-primary">{{ $tiangan[$pan['sike'][0]] }}</span>
                                                    <span class="justify-self-start text-xs font-medium text-base-content/45">{{ $wuxing[$wuxingTian[$pan['sike'][0]]] }}</span>
                                                @else
                                                    <span class="justify-self-end text-xs font-medium text-amber-700">{{ $xundunLabels[$pan['sike'][$upperIndex - 1]] }}</span>
                                                    <span class="text-2xl font-semibold text-primary">{{ $dizhi[$pan['sike'][$upperIndex - 1]] }}</span>
                                                    <span class="justify-self-start text-xs font-medium text-base-content/45">{{ $wuxing[$wuxingDi[$pan['sike'][$upperIndex - 1]]] }}</span>
                                                @endif
                                            </p>
                                        </div>
                                    @endforeach
                                </div>
                            </x-card>
                        </div>

                        <x-card title="天地盘" subtitle="十二宫位 · 天盘在上，地盘在下" class="pan-data-card pan-mobile-edge" shadow separator>
                            <div class="min-w-0 pb-1">
                                <div class="pan-board mx-auto w-full">
                                    @foreach ($palacePositions as $groundIndex => $position)
                                        <div class="pan-palace" style="grid-area: {{ $position }}">
                                            <span class="pan-ground" aria-label="地盘{{ $dizhi[$groundIndex] }}">{{ $dizhi[$groundIndex] }}</span>
                                            <x-badge
                                                :value="$tianjiangNames[$pan['tianjiang'][$groundIndex]]"
                                                class="badge-soft relative z-10 mb-1"
                                            />
                                            <p class="relative z-10 grid w-full grid-cols-[1fr_auto_1fr] items-baseline gap-1 px-1">
                                                <span class="justify-self-end text-xs font-medium text-amber-700">{{ $xundunLabels[$pan['tianpan'][$groundIndex]] }}</span>
                                                <strong class="text-2xl font-semibold text-primary">{{ $dizhi[$pan['tianpan'][$groundIndex]] }}</strong>
                                                <span class="justify-self-start text-xs font-medium text-base-content/45">{{ $wuxing[$wuxingDi[$pan['tianpan'][$groundIndex]]] }}</span>
                                            </p>
                                        </div>
                                    @endforeach

                                    <div class="pan-center">
                                        <span class="text-xs tracking-[0.3em] text-base-content/45">四柱</span>
                                        <strong class="mt-2 text-xl font-semibold tracking-wider">{{ $pan['sizhu'] }}</strong>
                                    </div>
                                </div>
                            </div>
                        </x-card>

                        <x-card title="解盘信息" subtitle="课体名称与取用说明" class="pan-data-card pan-mobile-edge" shadow separator>
                            <div class="space-y-4">
                                @foreach ($lessonInterpretations as $interpretation)
                                    <article class="rounded-2xl border border-base-300 bg-base-100 p-5">
                                        <div class="mb-3 flex items-center gap-3">
                                            <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-primary/10 text-sm font-semibold text-primary">课</span>
                                            <h2 class="text-lg font-semibold">{{ $interpretation['name'] }}</h2>
                                        </div>
                                        <p class="pl-11 leading-7 text-base-content/65">{{ $interpretation['description'] }}</p>
                                    </article>
                                @endforeach

                                @include('livewire.pan.partials.shehai-trace')
                            </div>
                        </x-card>
                    </div>
                @endif
            </section>
        </div>
    </main>
</div>
