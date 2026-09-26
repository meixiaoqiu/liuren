{{--
  毕法案例一行展示——列表式横向布局，每行一个案例。
  外层"例"字徽章已在 show.blade.php 大区块里渲染，这里不再重复。

  展示：
    - 来源分类 badge（kindLabel，如"《六壬大全》正文案例" / "程序验证案例"）；
    - 案例 label（标题）；
    - 案例真实 source（中文古籍 / 旁证来源，非硬编码）；
    - executable：整行 `<a target="_blank">`，新窗口打开排盘页；
    - reference_only / 无 datetime：纯展示行。

  props:
    - $case: array<string, mixed>  BiFaCaseCatalog 单条案例原始字段；
    - $kindLabel: string           来源分类标识，如 "《六壬大全》正文案例" / "程序验证案例"；
    - $kindTone: string            badge 颜色 tone，取值 "primary" / "ghost"。
--}}

@php
    $isExecutable = ($case['status'] ?? '') === 'executable';
    $isGenerated = ($case['source_type'] ?? '') === 'generated';
    $label = trim((string) ($case['label'] ?? ''));
    $rawReason = trim((string) ($case['reason'] ?? ''));
    $rawSource = trim((string) ($case['source'] ?? ''));
    $datetime = $case['datetime'] ?? null;
    $birth = $case['birth'] ?? null;
    $gender = $case['gender'] ?? null;
    $people = $case['people'] ?? [];

    // 程序验证案例的 reason 是工程审计说明（含 route code、单元测试名等内部术语），
    // 不展示给最终用户。古籍正文案例的 reason 是干净的中文描述，保留。
    $reason = $isGenerated ? '' : $rawReason;

    // 来源信息：古籍正文案例显示真实 source（如"《六壬大全·毕法赋》第九法·卷九；程树勋《壬学琐记》"）；
    // 程序验证案例沿用分类标识，不再拼接额外的"现代程序验证"前后缀。
    $sourceDisplay = $isGenerated ? '' : $rawSource;

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

    $badgeToneClass = ($kindTone ?? 'primary') === 'ghost'
        ? 'badge-ghost badge-sm'
        : 'badge-primary badge-soft badge-sm';
@endphp

@if ($href !== null)
    <a
        href="{{ $href }}"
        target="_blank"
        rel="noopener noreferrer"
        class="pan-case-row group flex flex-wrap items-center gap-x-3 gap-y-2 px-4 py-3 transition hover:bg-base-200/50 focus:outline-none focus-visible:bg-base-200/50 sm:flex-nowrap"
        aria-label="{{ $label !== '' ? $label : '案例' }} · 打开排盘（新窗口）"
    >
        <x-badge :value="$kindLabel" class="{{ $badgeToneClass }} shrink-0" />
        <strong class="min-w-0 break-words text-sm font-semibold text-base-content sm:truncate">{{ $label }}</strong>
        @if ($reason !== '')
            <span class="hidden min-w-0 flex-1 truncate text-sm text-base-content/55 md:inline">— {{ $reason }}</span>
        @endif
        @if ($sourceDisplay !== '')
            <span class="hidden min-w-0 basis-full truncate pl-0 text-xs text-base-content/40 sm:basis-auto sm:pl-2 lg:inline" title="{{ $sourceDisplay }}">{{ $sourceDisplay }}</span>
        @endif
        <span class="ml-auto inline-flex shrink-0 items-center gap-1 text-xs text-primary">
            <x-icon name="o-arrow-top-right-on-square" class="size-4" />
            <span>新窗口打开</span>
        </span>
    </a>
@else
    <div class="pan-case-row flex flex-wrap items-center gap-x-3 gap-y-2 px-4 py-3" aria-label="{{ $label !== '' ? $label : '案例' }}">
        <x-badge :value="$kindLabel" class="{{ $badgeToneClass }} shrink-0" />
        <strong class="min-w-0 break-words text-sm font-semibold text-base-content sm:truncate">{{ $label }}</strong>
        <x-badge value="原文参考盘 · 尚未完整复现" class="badge-warning badge-soft badge-sm shrink-0" />
        @if ($sourceDisplay !== '')
            <span class="hidden min-w-0 basis-full break-words pl-0 text-xs text-base-content/40 sm:basis-auto sm:pl-2 lg:inline" title="{{ $sourceDisplay }}">{{ $sourceDisplay }}</span>
        @endif
        @if ($reason !== '' && mb_strlen($reason) >= 12)
            <span class="hidden min-w-0 flex-1 truncate text-sm text-base-content/55 md:inline">— {{ $reason }}</span>
        @endif
    </div>
@endif