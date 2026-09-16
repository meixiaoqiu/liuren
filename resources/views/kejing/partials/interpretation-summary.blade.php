@php
    $mode = $mode ?? 'pan';
    $lessonPage = $lessonPage ?? \App\Support\KeJingPageCatalog::findByCode((string) ($interpretation['code'] ?? ''));
    $staticDefinition = $staticDefinition ?? null;
    $lessonName = (string) ($interpretation['name'] ?? '课经');
    $lessonBaseName = preg_replace('/课$/u', '', $lessonName) ?: $lessonName;

    /**
     * 详情页与排盘页共用同一份结构化数据：
     *  - detail 模式使用 PanRule::definition() 的完整静态定义；
     *  - pan 模式使用当前盘 RuleMatch.evidence 中只保留 matched=true 的项。
     */
    if ($mode === 'detail') {
        $foundations = $foundations ?? ($staticDefinition['foundations'] ?? []);
        $displayJudgments = $judgments ?? ($staticDefinition['judgments'] ?? []);
        $staticDescription = trim((string) ($staticDefinition['description'] ?? ''));
        $descriptionSource = $staticDescription !== ''
            ? $staticDescription
            : (string) ($interpretation['description'] ?? '');
        $staticXiang = $staticDefinition['xiang'] ?? null;
        $xiangSource = is_string($staticXiang) && trim($staticXiang) !== ''
            ? $staticXiang
            : ($interpretation['xiang'] ?? null);
    } else {
        $foundations = $foundations ?? ($interpretation['evidence']['foundations'] ?? []);
        $foundations = array_values(array_filter(
            $foundations,
            static fn (array $foundation): bool => array_key_exists('matched', $foundation)
                ? $foundation['matched'] === true
                : true,
        ));

        $displayJudgments = $judgments ?? ($interpretation['evidence']['judgments'] ?? []);
        $displayJudgments = array_values(array_filter(
            $displayJudgments,
            static fn (array $judgment): bool => array_key_exists('matched', $judgment)
                ? $judgment['matched'] === true
                : true,
        ));
        $descriptionSource = (string) ($interpretation['description'] ?? '');
        $xiangSource = $interpretation['xiang'] ?? null;
    }

    $effectMeta = [
        'increase' => ['label' => '增强', 'class' => 'badge-success badge-soft'],
        'reduce' => ['label' => '减损', 'class' => 'badge-warning badge-soft'],
        'resolve' => ['label' => '例外', 'class' => 'badge-info badge-soft'],
        'neutral' => ['label' => '中性', 'class' => 'badge-ghost'],
    ];
@endphp

<div class="flex items-center gap-3" data-kejing-pan-heading="compact">
    <div class="flex h-9 shrink-0 items-stretch">
        <span class="grid size-9 place-items-center bg-neutral text-sm font-semibold text-neutral-content">{{ $interpretation['marker'] }}</span>
        @if ($interpretation['gua'] !== null)
            <span class="flex items-center bg-primary/12 px-2.5 text-sm font-semibold text-primary">
                {{ $interpretation['gua'] }}卦
            </span>
        @endif
    </div>

    <div class="min-w-0 flex-1">
        <span class="text-xs text-base-content/45">
            @if ($lessonPage !== null)
                第 {{ $lessonPage['number'] }} 课 ·
            @endif
            {{ $interpretation['group'] }}
        </span>
        @if ($mode === 'detail')
            <h1 class="text-lg font-semibold">{{ $interpretation['name'] }}</h1>
        @else
            <h2 class="text-lg font-semibold">{{ $interpretation['name'] }}</h2>
        @endif
    </div>

    @if ($interpretation['guaSymbol'] !== null)
        <span
            class="grid size-20 shrink-0 place-items-center bg-primary/10 text-4xl leading-none text-primary"
            data-kejing-pan-gua="compact"
            data-kejing-gua-slot="present"
            aria-label="{{ $interpretation['gua'] }}卦卦符"
        >
            {{ $interpretation['guaSymbol'] }}
        </span>
    @else
        <span
            class="grid size-20 shrink-0 place-items-center bg-transparent text-4xl leading-none text-transparent"
            data-kejing-gua-slot="empty"
            aria-hidden="true"
        >
            <span class="invisible">䷀</span>
        </span>
    @endif
</div>

@if ($mode === 'pan')
    <p class="mt-2 leading-7 text-base-content/65">{{ $descriptionSource }}</p>
    @if ($xiangSource !== null)
        <p class="mt-2 italic leading-7 text-base-content/55">{{ $xiangSource }}</p>
    @endif
@else
    <section class="mt-5">
        <h3 class="text-sm font-semibold tracking-wide text-base-content/70">现代汉语描述</h3>
        <p class="mt-2 leading-7 text-base-content/65">{{ $descriptionSource }}</p>
    </section>

    <section class="mt-5">
        <h3 class="text-sm font-semibold tracking-wide text-base-content/70">象曰</h3>
        @if ($xiangSource !== null)
            <p class="mt-2 italic leading-7 text-base-content/55">{{ $xiangSource }}</p>
        @else
            <p class="mt-2 text-sm leading-6 text-base-content/45">当前正式规则尚未结构化录入本课《象曰》，页面不自行补写。</p>
        @endif
    </section>
@endif

<section class="pan-block mt-5 bg-base-200/45 px-4 py-4 sm:px-5" aria-label="{{ $interpretation['name'] }}：成立条件">
    <h3 class="text-sm font-semibold tracking-wide text-base-content/70">
        @if ($mode === 'pan')
            {{ $lessonBaseName }}判断 · 成立条件
        @else
            成立条件
        @endif
    </h3>

    @if ($foundations === [])
        <p class="mt-3 text-sm leading-6 text-base-content/45">
            @if ($mode === 'detail')
                当前正式规则尚未结构化录入本课成立条件，详情页不擅自以 description 反推。
            @else
                当前正式规则尚未结构化录入本课成立条件；解盘信息按 RuleMatch.evidence 输出。
            @endif
        </p>
    @else
        <ol class="mt-4 space-y-4">
            @foreach ($foundations as $index => $foundation)
                @php
                    $foundationCode = $foundation['code'] ?? null;
                    $foundationMatched = array_key_exists('matched', $foundation)
                        ? $foundation['matched'] === true
                        : ($mode === 'pan' ? true : null);
                    $foundationDetail = $foundation['evidence'] ?? $foundation['detail'] ?? null;
                    $foundationDescription = $foundation['description'] ?? '';
                @endphp
                <li class="grid grid-cols-[1.75rem_minmax(0,1fr)] gap-2">
                    <span class="grid size-7 place-items-center rounded-full bg-primary/12 text-xs font-semibold text-primary">{{ $index + 1 }}</span>
                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <strong>{{ $foundation['title'] }}</strong>
                            @if ($mode === 'detail')
                                @if ($foundationMatched === true)
                                    <x-badge value="本盘命中" class="badge-success badge-soft" />
                                @elseif ($foundationMatched === false)
                                    <x-badge value="本盘未命中" class="badge-ghost badge-soft" />
                                @endif
                            @elseif ($foundationMatched === true)
                                <x-badge value="已成立" class="badge-success badge-soft" />
                            @endif
                        </div>
                        <p class="mt-1 text-sm leading-6 text-base-content/65">{{ $foundationDescription }}</p>
                        @if ($foundationMatched === true && $foundationDetail !== null && $foundationDetail !== '')
                            <p class="mt-1 text-xs leading-5 text-base-content/55">当前盘：{{ $foundationDetail }}</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    @endif
</section>

@if ($mode === 'detail' || $displayJudgments !== [])
    <section class="pan-block mt-4 bg-base-200/45 px-4 py-4 sm:px-5" aria-label="{{ $interpretation['name'] }}：增益和减损条件">
        <h3 class="text-sm font-semibold tracking-wide text-base-content/70">增益和减损条件</h3>

        @if ($mode === 'detail' && $displayJudgments === [])
            <p class="mt-3 text-sm leading-6 text-base-content/45">
                @if (($staticDefinition['foundations'] ?? []) === [])
                    当前正式规则尚未结构化录入本课成立条件与增益减损条件，详情页不擅自以 description 反推。
                @else
                    当前正式规则未为本课定义独立的增益、减损或例外条件；详情页不擅自补写。
                @endif
            </p>
        @elseif ($displayJudgments === [])
            <p class="mt-3 text-sm leading-6 text-base-content/50">当前盘未触发任何独立增益、减损或例外条件。</p>
        @else
            <div class="mt-3 space-y-3">
                @foreach ($displayJudgments as $judgment)
                    @php
                        $judgmentMatched = array_key_exists('matched', $judgment)
                            ? $judgment['matched'] === true
                            : ($mode === 'pan' ? true : null);
                        $judgmentDescription = $judgment['description'] ?? '';
                        $judgmentEvidence = $judgment['evidence'] ?? null;
                    @endphp
                    <div class="border-l-2 border-primary/35 pl-4">
                        <div class="flex flex-wrap items-center gap-2">
                            @if (isset($judgment['effect'], $effectMeta[$judgment['effect']]))
                                <x-badge :value="$effectMeta[$judgment['effect']]['label']" class="{{ $effectMeta[$judgment['effect']]['class'] }}" />
                            @endif
                            <strong>{{ $judgment['label'] }}</strong>
                            @if ($mode === 'detail')
                                @if ($judgmentMatched === true)
                                    <x-badge value="本盘命中" class="badge-success badge-soft" />
                                @elseif ($judgmentMatched === false)
                                    <x-badge value="本盘未触发" class="badge-ghost badge-soft" />
                                @else
                                    <x-badge value="静态条件" class="badge-ghost badge-soft" />
                                @endif
                            @elseif ($judgmentMatched === true)
                                <x-badge value="已触发" class="badge-success badge-soft" />
                            @endif
                        </div>
                        <p class="mt-1 text-sm leading-6 text-base-content/60">{{ $judgmentDescription }}</p>
                        @if ($judgmentMatched === true && $judgmentEvidence !== null && $judgmentEvidence !== '')
                            <p class="mt-1 text-xs leading-5 text-base-content/55">当前盘：{{ $judgmentEvidence }}</p>
                        @endif
                        @if ($mode === 'detail' && ! empty($judgment['examples'] ?? []))
                            <p class="mt-1 text-xs leading-5 text-base-content/40">课例覆盖：{{ implode('、', $judgment['examples']) }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </section>
@endif

@if ($mode === 'pan' && $lessonPage !== null)
    <div class="mt-4 flex justify-end">
        <x-button
            :label="'查看'.$interpretation['name'].'详解'"
            icon-right="o-arrow-right"
            :link="route('kejing.show', ['lesson' => $lessonPage['slug']])"
            class="btn-ghost btn-sm"
        />
    </div>
@endif