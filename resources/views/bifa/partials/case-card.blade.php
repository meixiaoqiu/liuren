<div class="block h-full">
    <x-card :title="$case['label']" class="h-full bg-base-100/70">
        <x-slot:menu>
            @if (! empty($kind ?? null))
                <x-badge :value="$kind" class="badge-ghost badge-sm" />
            @endif
            <x-badge :value="$case['status_label']" class="badge-{{ $case['status_tone'] }} badge-soft" />
            @if ($case['url'] !== null)
                <x-button label="查看排盘" :link="$case['url']" class="btn-ghost btn-xs" />
            @endif
        </x-slot:menu>

        <x-alert icon="o-light-bulb" class="alert-soft">
            {{ $case['description'] }}
        </x-alert>
    </x-card>
</div>
