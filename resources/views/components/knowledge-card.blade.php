@props(['card'])

@php
    $badgeClass = static fn (string $tone): string => match ($tone) {
        'success' => 'badge-success badge-soft',
        'warning' => 'badge-warning badge-soft',
        'info' => 'badge-info badge-soft',
        default => 'badge-ghost badge-soft',
    };
    $cardId = md5($card['type'].$card['code'].$card['title']);
@endphp

<x-card :title="$card['title']" :subtitle="$card['summary']" shadow>
    <x-slot:menu>
        <x-badge :value="$card['type']" class="badge-primary badge-soft" />
        <x-badge :value="$card['code']" class="badge-ghost" />
        @if ($card['status'] !== null)
            <x-badge
                :value="$card['status']['label']"
                class="{{ $badgeClass($card['status']['tone']) }}"
            />
        @endif
    </x-slot:menu>

    @foreach ($card['sections'] as $section)
        <x-alert icon="o-information-circle" class="mb-3 alert-soft">
            <strong>{{ $section['title'] }}</strong>
            <p class="mt-1 text-sm leading-6">{{ $section['content'] }}</p>
        </x-alert>
    @endforeach

    @if ($card['conditions'] !== [])
        <x-collapse :id="'conditions-'.$cardId" collapse-plus-minus class="mt-4">
            <x-slot:heading><strong>成立条件</strong></x-slot:heading>
            <x-slot:content>
                <ul class="space-y-3">
                    @foreach ($card['conditions'] as $condition)
                        <li>
                            <div class="flex flex-wrap items-center gap-2">
                                <strong>{{ $condition['title'] }}</strong>
                                @if ($condition['status'] !== null)
                                    <x-badge
                                        :value="$condition['status']['label']"
                                        class="{{ $badgeClass($condition['status']['tone']) }} badge-sm"
                                    />
                                @endif
                            </div>
                            <p class="mt-1 text-sm leading-6 text-base-content/65">{{ $condition['description'] }}</p>
                            @if ($condition['detail'] !== null && $condition['detail'] !== '')
                                <p class="mt-1 text-sm leading-6 text-base-content/55">当前盘：{{ $condition['detail'] }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </x-slot:content>
        </x-collapse>
    @endif

    @if ($card['evidence'] !== [])
        <x-collapse :id="'evidence-'.$cardId" collapse-plus-minus class="mt-3">
            <x-slot:heading><strong>判定依据</strong></x-slot:heading>
            <x-slot:content>
                <ul class="space-y-2">
                    @foreach ($card['evidence'] as $item)
                        <li><strong>{{ $item['label'] }}</strong>：{{ $item['detail'] }}</li>
                    @endforeach
                </ul>
            </x-slot:content>
        </x-collapse>
    @endif

    @if ($card['examples'] !== [])
        <x-collapse :id="'examples-'.$cardId" collapse-plus-minus class="mt-3">
            <x-slot:heading><strong>相关案例</strong></x-slot:heading>
            <x-slot:content>
                <ul class="space-y-3">
                    @foreach ($card['examples'] as $example)
                        <li>
                            <div class="flex flex-wrap items-center gap-2">
                                <strong>{{ $example['title'] }}</strong>
                                <x-badge :value="$example['source']" class="badge-primary badge-soft badge-sm" />
                                <x-badge
                                    :value="$example['status']['label']"
                                    class="{{ $badgeClass($example['status']['tone']) }} badge-sm"
                                />
                            </div>
                            <p class="mt-1 text-sm leading-6 text-base-content/60">{{ $example['description'] }}</p>
                            @if ($example['url'] !== null)
                                <x-button label="查看排盘" :link="$example['url']" class="mt-2 btn-ghost btn-xs" />
                            @endif
                        </li>
                    @endforeach
                </ul>
            </x-slot:content>
        </x-collapse>
    @endif

    @if ($card['actions'] !== [])
        <div class="mt-4 flex flex-wrap justify-end gap-2">
            @foreach ($card['actions'] as $action)
                <x-button
                    :label="$action['label']"
                    :link="$action['url']"
                    :icon-right="$action['icon']"
                    :external="$action['external']"
                    class="btn-ghost btn-sm"
                />
            @endforeach
        </div>
    @endif
</x-card>
