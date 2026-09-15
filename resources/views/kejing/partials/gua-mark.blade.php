@php
    $compact = $compact ?? false;
    $hasGua = $guaSymbol !== null;
    $boxClass = $compact
        ? 'size-24 text-7xl sm:size-28 sm:text-8xl'
        : 'size-28 text-8xl sm:size-32 sm:text-9xl';
@endphp

<div
    class="grid {{ $boxClass }} shrink-0 place-items-center leading-none {{ $hasGua ? 'bg-primary/10 text-primary' : 'bg-transparent text-transparent' }}"
    data-kejing-gua-slot="{{ $hasGua ? 'present' : 'empty' }}"
    @if ($hasGua) aria-label="{{ $gua }}卦卦符" @else aria-hidden="true" @endif
>
    @if ($hasGua)
        {{ $guaSymbol }}
    @else
        <span class="invisible">䷀</span>
    @endif
</div>
