{{--
  相关案例列表子组件——只负责呈现 examples 数组。

  本子组件不含外层 x-card——它被设计为嵌入 knowledge-card 内部的一段内容，
  外框由父级 knowledge-card 提供，避免 MaryUI <x-card> 重复嵌套。

  props:
   - $examples: list<array{title: string, description: string, source: string, status: array{label: string, tone: string}, url: ?string}>
--}}

@props(['examples'])

@if (! empty($examples))
    <section class="mt-5" aria-label="相关案例">
        <h3 class="text-sm font-semibold tracking-wide text-base-content/70">相关案例</h3>
        <ul class="mt-3 space-y-3" role="list">
            @foreach ($examples as $example)
                <li>
                    <div class="flex flex-wrap items-center gap-2">
                        <strong>{{ $example['title'] }}</strong>
                        <span class="text-xs text-base-content/55">{{ $example['source'] }}</span>
                        <x-knowledge.knowledge-status :status="$example['status']" size="text-xs" />
                    </div>
                    <p class="mt-1 text-sm leading-6 text-base-content/60">{{ $example['description'] }}</p>
                    @if (! empty($example['url']))
                        <x-button label="查看排盘" :link="$example['url']" class="mt-2 btn-ghost btn-xs" />
                    @endif
                </li>
            @endforeach
        </ul>
    </section>
@endif
