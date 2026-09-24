@props(['card'])

@php
    $statusMeta = static fn (string $tone): array => match ($tone) {
        'success' => ['icon' => 'o-check-circle', 'iconClass' => 'text-success', 'textClass' => 'text-base-content'],
        'warning' => ['icon' => 'o-clock', 'iconClass' => 'text-warning', 'textClass' => 'text-base-content/70'],
        'info' => ['icon' => 'o-information-circle', 'iconClass' => 'text-info', 'textClass' => 'text-base-content/70'],
        default => ['icon' => 'o-x-circle', 'iconClass' => 'text-base-content/35', 'textClass' => 'text-base-content/45'],
    };
    $typeMarker = mb_substr($card['type'], 0, 1);
@endphp

<div>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="flex min-w-0 items-center gap-3">
            <span class="grid size-9 shrink-0 place-items-center bg-neutral text-sm font-semibold text-neutral-content">
                {{ $typeMarker }}
            </span>
            <h2 class="text-lg font-semibold">{{ $card['title'] }}</h2>
        </div>

        @if ($card['status'] !== null)
            @php($meta = $statusMeta($card['status']['tone']))
            <span class="flex items-center gap-1.5 text-sm font-medium {{ $meta['textClass'] }}">
                <x-icon :name="$meta['icon']" class="size-5 {{ $meta['iconClass'] }}" />
                {{ $card['status']['label'] }}
            </span>
        @endif

        <p class="w-full leading-7 text-base-content/65">{{ $card['summary'] }}</p>
    </div>

    @foreach ($card['sections'] as $section)
        <x-alert icon="o-information-circle" class="mt-4 alert-soft">
            <strong>{{ $section['title'] }}</strong>
            <p class="mt-1 text-sm leading-6">{{ $section['content'] }}</p>
        </x-alert>
    @endforeach

    @if ($card['conditions'] !== [])
        <section class="mt-5 bg-base-200/45 px-4 py-4 sm:px-5" aria-label="{{ $card['title'] }}：成立条件">
            <h3 class="text-sm font-semibold tracking-wide text-base-content/70">成立条件</h3>
            <ol class="mt-4 space-y-4">
                @foreach ($card['conditions'] as $index => $condition)
                    <li class="grid grid-cols-[1.75rem_minmax(0,1fr)] gap-2">
                        <span class="grid size-7 place-items-center rounded-full bg-primary/12 text-xs font-semibold text-primary">{{ $index + 1 }}</span>
                        <div>
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <strong>{{ $condition['title'] }}</strong>
                                @if ($condition['status'] !== null)
                                    @php($meta = $statusMeta($condition['status']['tone']))
                                    <span class="flex items-center gap-1.5 text-sm font-medium {{ $meta['textClass'] }}">
                                        <x-icon :name="$meta['icon']" class="size-5 {{ $meta['iconClass'] }}" />
                                        {{ $condition['status']['label'] }}
                                    </span>
                                @endif
                            </div>
                            <p class="mt-1 text-sm leading-6 text-base-content/65">{{ $condition['description'] }}</p>
                            @if ($condition['detail'] !== null && $condition['detail'] !== '')
                                <p class="mt-1 text-xs leading-5 text-base-content/55">当前盘：{{ $condition['detail'] }}</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif

    @if ($card['examples'] !== [])
        <section class="mt-4 bg-base-200/45 px-4 py-4 sm:px-5" aria-label="{{ $card['title'] }}：相关案例">
            <h3 class="text-sm font-semibold tracking-wide text-base-content/70">相关案例</h3>
            <ul class="mt-3 space-y-4">
                @foreach ($card['examples'] as $example)
                    <li class="border-l-2 border-primary/35 pl-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <strong>{{ $example['title'] }}</strong>
                            <span class="text-xs text-base-content/55">{{ $example['source'] }}</span>
                            @php($meta = $statusMeta($example['status']['tone']))
                            <span class="flex items-center gap-1 text-xs font-medium {{ $meta['textClass'] }}">
                                <x-icon :name="$meta['icon']" class="size-4 {{ $meta['iconClass'] }}" />
                                {{ $example['status']['label'] }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm leading-6 text-base-content/60">{{ $example['description'] }}</p>
                        @if ($example['url'] !== null)
                            <x-button label="查看排盘" :link="$example['url']" class="mt-2 btn-ghost btn-xs" />
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
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
</div>
