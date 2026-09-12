@include('livewire.pan.partials.lesson-trace', ['title' => '天狱判断', 'trace' => $trace])

@if (! empty($trace['uncovered']))
    <details class="mt-4 text-sm text-base-content/55">
        <summary class="cursor-pointer">课义增强、凶应修证与尚未实现项</summary>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach ($trace['uncovered'] as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    </details>
@endif
