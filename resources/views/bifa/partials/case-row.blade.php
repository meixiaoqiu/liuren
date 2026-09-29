{{--
  毕法案例一行展示——列表式横向布局，每行一个案例。
  外层"例"字徽章已在 show.blade.php 大区块里渲染，这里不再重复。

  展示结构（第一行 + 第二行 子行）：
    第一行：
      [分类 badge]  案例标题  [状态 badge / 打开排盘]
    第二行（移动端始终可见；桌面与其它元素同行）：
      真实来源  — 必要时再显示 reason

  executable case：整行 `<a target="_blank">`，新窗口打开排盘页；
  reference_only / 无 datetime：纯展示行。
  长来源字符串必须 `break-words` / `min-w-0`，不得造成横向滚动；不依赖 `title` 兜底。

  props:
    - $case: array<string, mixed>  BiFaCaseCatalog 单条案例原始字段；
    - $kindLabel: string           来源分类标识，如 "古籍案例" / "程序验证案例"；
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
    // 不展示给最终用户。古籍案例的 reason 是干净的中文描述，保留。
    $reason = $isGenerated ? '' : $rawReason;

    // 来源信息：古籍案例显示真实 source（如"《六壬大全·毕法赋》第九法·卷九；程树勋《壬学琐记》"）；
    // 程序验证案例不展示 source（避免泄漏内部术语）。
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

    $showSecondLine = $sourceDisplay !== '' || ($reason !== '' && mb_strlen($reason) >= 12);
@endphp

@if ($href !== null)
    <a
        href="{{ $href }}"
        target="_blank"
        rel="noopener noreferrer"
        class="pan-case-row group block px-4 py-3 transition hover:bg-base-200/50 focus:outline-none focus-visible:bg-base-200/50"
        aria-label="{{ $label !== '' ? $label : '案例' }} · 打开排盘（新窗口）"
    >
        <div class="flex flex-wrap items-center gap-x-3 gap-y-2 sm:flex-nowrap">
            <x-badge :value="$kindLabel" class="{{ $badgeToneClass }} shrink-0" />
            <strong class="min-w-0 break-words text-sm font-semibold text-base-content sm:truncate">{{ $label }}</strong>
            <span class="ml-auto inline-flex shrink-0 items-center gap-1 text-xs text-primary">
                <x-icon name="o-arrow-top-right-on-square" class="size-4" />
                <span>新窗口打开</span>
            </span>
        </div>
        @if ($showSecondLine)
            <div class="mt-1.5 flex flex-wrap items-baseline gap-x-3 gap-y-1 text-xs text-base-content/55 sm:flex-nowrap">
                @if ($sourceDisplay !== '')
                    <span class="min-w-0 break-words">{{ $sourceDisplay }}</span>
                @endif
                @if ($sourceDisplay !== '' && $reason !== '' && mb_strlen($reason) >= 12)
                    <span class="hidden text-base-content/35 md:inline">·</span>
                @endif
                @if ($reason !== '' && mb_strlen($reason) >= 12)
                    <span class="hidden min-w-0 truncate md:inline">— {{ $reason }}</span>
                @endif
            </div>
        @endif
    </a>
@else
    <div class="pan-case-row block px-4 py-3" aria-label="{{ $label !== '' ? $label : '案例' }}">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-2 sm:flex-nowrap">
            <x-badge :value="$kindLabel" class="{{ $badgeToneClass }} shrink-0" />
            <strong class="min-w-0 break-words text-sm font-semibold text-base-content sm:truncate">{{ $label }}</strong>
            <x-badge value="原文参考盘 · 尚未完整复现" class="badge-warning badge-soft badge-sm shrink-0" />
        </div>
        @if ($sourceDisplay !== '')
            <div class="mt-1.5 text-xs text-base-content/55">
                <span class="block min-w-0 break-words">{{ $sourceDisplay }}</span>
            </div>
        @endif
        @if ($reason !== '' && mb_strlen($reason) >= 12)
            <div class="mt-1 hidden text-xs text-base-content/55 md:block">
                <span class="block min-w-0 break-words">— {{ $reason }}</span>
            </div>
        @endif
    </div>
@endif
