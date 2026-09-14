@include('livewire.pan.partials.lesson-trace', ['title' => '盘珠判断', 'trace' => $trace])

@if (! empty($trace['uncovered'] ?? []))
    <section class="pan-block mt-4 bg-base-200/45 px-4 py-4 sm:px-5" aria-label="盘珠尚未覆盖">
        <h4 class="text-sm font-semibold tracking-wide text-base-content/70">尚未覆盖</h4>
        <ul class="mt-3 list-disc space-y-2 pl-5 text-sm leading-6 text-base-content/60">
            @foreach ($trace['uncovered'] as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    </section>
@endif
