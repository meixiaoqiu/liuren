{{--
  毕法案例一行展示——列表式横向布局，每行一个案例。
  外层"例"字徽章已在 show.blade.php 大区块里渲染，这里不再重复。
  executable case：整行 `<a target="_blank">` 包住，新窗口打开排盘页；
  reference_only / 无 datetime：纯展示行。
  来源 label（$sourceLabel）由外层循环传入，标识案例归属。

  props:
    - $case: array<string, mixed>  BiFaCaseCatalog 单条案例原始字段；
    - $sourceLabel: string         来源标签，如 "《六壬大全》正文案例" / "程序验证案例"。
--}}

@php
    $isExecutable = ($case['status'] ?? '') === 'executable';
    $isGenerated = ($case['source_type'] ?? '') === 'generated';
    $label = trim((string) ($case['label'] ?? ''));
    $rawReason = trim((string) ($case['reason'] ?? ''));
    $datetime = $case['datetime'] ?? null;
    $birth = $case['birth'] ?? null;
    $gender = $case['gender'] ?? null;
    $people = $case['people'] ?? [];

    // 仅在古籍正文案例（source_type !== 'generated'）上展示 reason：
    // 程序验证案例的 reason 主要是工程审计说明（包含 route code、单元测试名、livewire 等内部术语），
    // 不适合展示给最终用户。
    $reason = $isGenerated ? '' : $rawReason;

    if ($isExecutable && $datetime !== null) {
        $query = array_filter([
            'datetime' => $datetime,
            'birth' => $birth,
            'gender' => $gender,
            'people' => empty($people) ? null : $people,
        ], static fn ($value): bool => $value !== null && $value !== '');
        $href = route('pan.create', $query);
    } else {
        $href = null;
    }
@endphp

@if ($href !== null)
    <a
        href="{{ $href }}"
        target="_blank"
        rel="noopener noreferrer"
        class="pan-case-row group flex items-center gap-3 px-4 py-3 transition hover:bg-base-200/50 focus:outline-none focus-visible:bg-base-200/50"
        aria-label="{{ $label !== '' ? $label : '案例' }} · 打开排盘（新窗口）"
    >
        <x-badge :value="$sourceLabel" class="{{ $isGenerated ? 'badge-ghost badge-sm' : 'badge-primary badge-soft badge-sm' }}" />
        <strong class="min-w-0 truncate text-sm font-semibold text-base-content">{{ $label }}</strong>
        @if ($reason !== '')
            <span class="hidden min-w-0 truncate text-sm text-base-content/55 md:inline">— {{ $reason }}</span>
        @endif
        <span class="ml-auto inline-flex shrink-0 items-center gap-1 text-xs text-primary">
            <x-icon name="o-arrow-top-right-on-square" class="size-4" />
            <span>新窗口打开</span>
        </span>
    </a>
@else
    <div class="pan-case-row flex items-center gap-3 px-4 py-3" aria-label="{{ $label !== '' ? $label : '案例' }}">
        <x-badge :value="$sourceLabel" class="{{ $isGenerated ? 'badge-ghost badge-sm' : 'badge-primary badge-soft badge-sm' }}" />
        <strong class="min-w-0 truncate text-sm font-semibold text-base-content">{{ $label }}</strong>
        <x-badge value="原文参考盘 · 尚未完整复现" class="badge-warning badge-soft badge-sm" />
        @if ($reason !== '' && mb_strlen($reason) >= 12)
            <span class="hidden min-w-0 truncate text-sm text-base-content/55 md:inline">— {{ $reason }}</span>
        @endif
    </div>
@endif