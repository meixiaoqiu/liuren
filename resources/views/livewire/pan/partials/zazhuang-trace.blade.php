@include('livewire.pan.partials.lesson-trace', ['title' => '杂状判断', 'trace' => $trace, 'suppressCoreTrace' => $suppressCoreTrace ?? false])

<section class="pan-block mt-4 bg-base-200/45 px-4 py-4 sm:px-5" aria-label="杂状取象">
    <h3 class="text-sm font-semibold tracking-wide text-base-content/70">杂状取象</h3>
    <dl class="mt-3 grid gap-3 text-sm sm:grid-cols-2">
        <div><dt class="text-base-content/45">初传与分类</dt><dd class="mt-1 font-medium">初传：{{ $trace['initial_name'] }} · 分类：{{ $trace['mixed_subtype_label'] ?? $trace['purity_label'] }}</dd></div>
        <div><dt class="text-base-content/45">加临</dt><dd class="mt-1 font-medium">{{ $trace['initial_name'] }}加{{ $trace['ground_name'] }}（加临地盘{{ $trace['ground_name'] }}）</dd></div>
        <div><dt class="text-base-content/45">五行</dt><dd class="mt-1">上神{{ $trace['initial_name'] }}{{ $trace['upper_element_name'] }}；下神{{ $trace['ground_name'] }}{{ $trace['lower_element_name'] }}</dd></div>
        <div><dt class="text-base-content/45">物色</dt><dd class="mt-1">上{{ implode('、', $trace['upper_colors']) }}；下{{ implode('、', $trace['lower_colors']) }}</dd></div>
        <div class="sm:col-span-2"><dt class="text-base-content/45">太玄数</dt><dd class="mt-1">{{ $trace['initial_name'] }}{{ $trace['upper_number'] }} × {{ $trace['ground_name'] }}{{ $trace['lower_number'] }} = {{ $trace['base_number'] }}；当前{{ $trace['initial_name'] }}{{ $trace['upper_element_name'] }}为{{ $trace['seasonal_state'] }}，修正系数 ×{{ $trace['number_multiplier'] }}，修正数：{{ $trace['adjusted_number'] }}</dd></div>
    </dl>
    <p class="mt-3 text-xs leading-5 text-base-content/50">太玄数及旺衰修正属于传统杂状取象，用于古籍中的数量、日期、应期等推断；本程序仅复原其计算规则。</p>
</section>
