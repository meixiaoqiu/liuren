{{--
  KnowledgeCard 统一渲染组件。

  本组件不区分毕法 / 课经 / 格，不基于 `card.type` 做任何业务分支判断；
  所有用户可见文字、tone、marker 均由 Factory 注入。

  展示顺序（所有知识卡片必须遵守，禁止未来毕法/课经/格自行调整）：
    1. 类型 + 编号（type_label + label）
    2. 标题（title）
    3. 简述（summary）
    4. 成立状态（status）
    5. 成立条件（conditions）
    6. 命中依据（evidence）
    7. 增益/减损或说明（sections）
    8. 课例（examples）
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
    $badgeValue = trim((string) ($card['type_label'] ?? ''));
    $badgeNumber = trim((string) ($card['label'] ?? ''));
    if ($badgeNumber !== '') {
        $badgeValue = $badgeValue === '' ? $badgeNumber : ($badgeValue . ' · ' . $badgeNumber);
    }
@endphp

<x-card shadow class="pan-data-card">
    {{-- 1. 类型 + 编号（label 为空时只显示 type） --}}
    @if ($badgeValue !== '')
        <x-badge value="{{ $badgeValue }}" class="badge-primary badge-soft badge-sm gap-1" />
    @endif

    {{-- 2. 标题 --}}
    <h2 class="mt-2 text-xl font-semibold tracking-wide text-base-content sm:text-2xl">
        {{ $card['title'] ?? '' }}
    </h2>

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

    {{-- 7. 增益/减损或说明 --}}
    @if (! empty($sections))
        <section class="mt-5 space-y-3" aria-label="增益与减损说明">
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
</x-card>
