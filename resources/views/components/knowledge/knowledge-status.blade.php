@use('App\Support\Knowledge\KnowledgeCard')

@props([
    'status',
    'size' => 'text-sm',
])

@php
    $toneMeta = [
        KnowledgeCard::TONE_SUCCESS => ['icon' => '✔️', 'textClass' => 'font-bold text-base-content'],
        KnowledgeCard::TONE_WARNING => ['icon' => '⏳', 'textClass' => 'font-medium text-base-content/70'],
        KnowledgeCard::TONE_INFO => ['icon' => 'ℹ️', 'textClass' => 'font-medium text-base-content/70'],
        KnowledgeCard::TONE_NEUTRAL => ['icon' => '❌', 'textClass' => 'font-normal text-base-content/45'],
    ];
    $meta = $toneMeta[$status['tone']] ?? $toneMeta[KnowledgeCard::TONE_NEUTRAL];
@endphp

<span class="flex items-center gap-1.5 {{ $size }} {{ $meta['textClass'] }}">
    <span aria-hidden="true">{{ $meta['icon'] }}</span>
    {{ $status['label'] }}
</span>
