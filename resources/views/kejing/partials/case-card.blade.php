@php
    $query = [
        'datetime' => $case['datetime'],
        'birth' => $case['birth'],
        'gender' => $case['gender'],
    ];
    if (! empty($case['people'] ?? [])) {
        $query['people'] = $case['people'];
    }
    if (($case['status'] ?? 'executable') === 'reference_only' && ! empty($case['case_id'] ?? null)) {
        $query['reference_case'] = $case['case_id'];
    }
@endphp

<a href="{{ route('pan.create', $query) }}" class="block h-full transition hover:opacity-90">
    <x-card :title="$case['label']" class="h-full bg-base-100/70">
        <x-slot:menu>
            @if (! empty($kind ?? null))
                <x-badge :value="$kind" class="badge-ghost badge-sm" />
            @endif
            @if (($case['status'] ?? 'executable') === 'reference_only')
                <x-badge value="原文参考盘·尚未覆盖" class="badge-warning badge-soft" />
            @endif
            <x-icon name="o-arrow-top-right-on-square" class="text-primary" />
        </x-slot:menu>

        <x-alert icon="o-light-bulb" class="alert-soft">
            {{ $case['reason'] }}
        </x-alert>
    </x-card>
</a>
