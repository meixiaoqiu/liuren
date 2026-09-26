{{--
  KnowledgeCard 统一渲染组件。

  本组件不区分毕法 / 课经 / 格，不基于 `card.type` 做任何业务分支判断；
  所有用户可见文字、tone、marker 均由 Factory 注入。

  展示顺序（所有知识卡片必须遵守，禁止未来毕法/课经/格自行调整）：
    1. 类型 + 编号（type_label + label）
    2. 标题（title，与体系字标合在同一行内，长方形框承载，h2 语义）
    3. 简述（summary）
    4. 成立状态（status）
    5. 成立条件（conditions）
    6. 命中依据（evidence）
    7. 补充说明（sections）
    8. 课例（examples，由详情页大区块独立渲染时不渲染）
    9. 来源/详情入口（actions）

  props:
   - $card: array<string, mixed>
--}}

@props(['card'])

@php
    $status = $card['status'] ?? null;
    $conditions = $card['conditions'] ?? [];
    $sections = $card['sections'] ?? [];
    $examples = $card['examples'] ?? [];
    $actions = $card['actions'] ?? [];
    $evidence = $card['evidence'] ?? [];
    $typeLabel = trim((string) ($card['type_label'] ?? ''));
    $typeMarker = mb_substr($typeLabel, 0, 1);
@endphp

{{-- 1–2. 体系字标 + 标题行：长方形框里直接放标题，使用 h2 保留 heading 语义 --}}
@php
    $cardTitle = trim((string) ($card['title'] ?? ''));
@endphp
@if ($typeMarker !== '')
    <div class="flex h-9 shrink-0 items-stretch">
        <span class="grid size-9 place-items-center bg-neutral text-sm font-semibold text-neutral-content">
            {{ $typeMarker }}
        </span>
        @if ($cardTitle !== '')
            <h2 class="flex items-center bg-primary/12 px-2.5 text-sm font-semibold text-primary">{{ $cardTitle }}</h2>
        @endif
    </div>
@endif

{{-- 3. 简述 --}}
@if (! empty($card['summary']))
    <p class="mt-2 max-w-3xl text-sm leading-7 text-base-content/65">{{ $card['summary'] }}</p>
@endif

{{-- 4. 成立状态 --}}
@if ($status !== null)
    <div class="mt-3">
        <x-knowledge.knowledge-status :status="$status" />
    </div>
@endif

{{-- 5. 成立条件 --}}
<x-knowledge.knowledge-condition-list :conditions="$conditions" />

{{-- 6. 命中依据 --}}
@if (! empty($evidence))
    <section class="mt-4" aria-label="命中依据">
        <h3 class="text-sm font-semibold tracking-wide text-base-content/70">命中依据</h3>
        <div class="mt-3 space-y-2">
            @foreach ($evidence as $item)
                <x-alert icon="o-light-bulb" class="alert-soft">
                    <strong>{{ $item['label'] }}</strong>
                    <p class="mt-1 text-sm leading-6 text-base-content/65">{{ $item['detail'] }}</p>
                </x-alert>
            @endforeach
        </div>
    </section>
@endif

{{-- 7. 补充说明 --}}
@if (! empty($sections))
    <section class="mt-5 space-y-3" aria-label="补充说明">
        @foreach ($sections as $section)
            <x-alert icon="o-information-circle" class="alert-soft">
                <strong>{{ $section['title'] }}</strong>
                <p class="mt-1 text-sm leading-6">{{ $section['content'] }}</p>
            </x-alert>
        @endforeach
    </section>
@endif

{{-- 8. 课例 --}}
<x-knowledge.knowledge-example-list :examples="$examples" />

{{-- 9. 来源/详情入口 --}}
@if (! empty($actions))
    <div class="mt-4 flex flex-wrap justify-end gap-2">
        @foreach ($actions as $action)
            <x-button
                :label="$action['label']"
                :link="$action['url']"
                :icon-right="$action['icon'] ?? null"
                :external="$action['external'] ?? false"
                class="btn-ghost btn-sm"
            />
        @endforeach
    </div>
@endif
