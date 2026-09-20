@include('livewire.pan.partials.lesson-trace', ['title' => '物类课依据', 'trace' => $trace, 'suppressCoreTrace' => $suppressCoreTrace ?? false])

<section class="pan-block mt-4 bg-base-200/45 px-4 py-4 sm:px-5" aria-label="物类取象">
    <h3 class="text-sm font-semibold tracking-wide text-base-content/70">物类取象</h3>
    <dl class="mt-3 grid gap-3 text-sm">
        @foreach (['initial' => '初传', 'middle' => '中传', 'final' => '末传'] as $key => $label)
            @php($item = $trace[$key])
            <div>
                <dt class="text-base-content/45">{{ $label }}</dt>
                <dd class="mt-1 font-medium">{{ $item['branch_name'] ?? '—' }} · 五行{{ $item['element_name'] ?? '—' }} · 六亲{{ $item['liuqin_name'] ?? '—' }} · 时令{{ $item['seasonal_state'] ?? '—' }} · 乘{{ $item['general_name'] ?? '—' }}将</dd>
            </div>
        @endforeach
    </dl>
    <p class="mt-3 text-xs leading-5 text-base-content/50">六亲、旺衰和天将均为物类取象事实；亲疏、新旧、过去未来和始终吉凶的完整古法尚未程序化。</p>
</section>
