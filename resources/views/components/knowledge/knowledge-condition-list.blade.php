{{--
  成立条件列表子组件——只负责呈现 conditions 数组。

  本子组件不含外层 x-card——它被设计为嵌入 knowledge-card 内部的一段内容。
  包含关系：knowledge-card > knowledge-condition-list。

  props:
   - $conditions: list<array{marker: string, title: string, description: string, status: ?array, detail: ?string}>
--}}

@props(['conditions'])

@if (! empty($conditions))
    <section class="mt-5" aria-label="成立条件">
        <h3 class="text-sm font-semibold tracking-wide text-base-content/70">成立条件</h3>
        <ul class="mt-3 space-y-3" role="list">
            @foreach ($conditions as $condition)
                @php($marker = $condition['marker'] ?? '⏺')
                <li class="flex items-start gap-3">
                    <span class="grid size-7 shrink-0 place-items-center text-base" aria-hidden="true">{{ $marker }}</span>
                    <div class="w-full">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <strong>{{ $condition['title'] ?? '' }}</strong>
                            @if (! empty($condition['status']))
                                <x-knowledge.knowledge-status :status="$condition['status']" />
                            @endif
                        </div>
                        <p class="mt-1 text-sm leading-6 text-base-content/65">{{ $condition['description'] ?? '' }}</p>
                        @if (! empty($condition['detail']))
                            <p class="mt-1 text-xs leading-5 text-base-content/55">当前盘：{{ $condition['detail'] }}</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </section>
@endif
