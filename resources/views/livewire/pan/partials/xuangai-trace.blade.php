@php
    $branchRoles = [
        ['title' => '胜光发用', 'branch' => $trace['initial_branch'], 'role' => '初传午（胜光）为天马'],
        ['title' => '太冲居中', 'branch' => $trace['middle_branch'], 'role' => '中传卯（太冲）为天驷天车'],
        ['title' => '神后居末', 'branch' => $trace['final_branch'], 'role' => '末传子（神后）为紫微华盖'],
    ];
@endphp

<section class="pan-block mt-4 overflow-hidden bg-base-200/45" aria-label="轩盖判断过程">
    <h3 class="px-4 pt-4 font-semibold sm:px-5">轩盖判断</h3>

    <div class="mt-4 grid lg:grid-cols-[minmax(0,1.1fr)_minmax(18rem,0.9fr)]">
        <div class="px-4 py-4 sm:px-5 lg:border-r lg:border-base-300/70">
            <h4 class="text-sm font-semibold tracking-wide text-base-content/70">成立依据</h4>
            <ol class="mt-4 space-y-4">
                @foreach ($branchRoles as $index => $branchRole)
                    <li class="grid grid-cols-[1.75rem_minmax(0,1fr)] gap-2">
                        <span class="grid size-7 place-items-center rounded-full bg-primary/12 text-xs font-semibold text-primary">{{ $index + 1 }}</span>
                        <div>
                            <strong>{{ $branchRole['title'] }}</strong>
                            <p class="mt-1 text-sm leading-6 text-base-content/65">{{ $branchRole['role'] }}。</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>

        <div class="border-t border-base-300/70 px-4 py-4 sm:px-5 lg:border-t-0">
            <h4 class="text-sm font-semibold tracking-wide text-base-content/70">已核实的课义条件</h4>
            @if ($trace['conditions'] === [])
                <p class="mt-3 text-sm leading-6 text-base-content/55">当前未命中已核实的附加条件。</p>
            @else
                <div class="mt-3 space-y-3">
                    @foreach ($trace['conditions'] as $condition)
                        <div class="border-l-2 border-primary/35 pl-4">
                            <strong class="block">{{ $condition['label'] }}</strong>
                            <p class="mt-1 text-sm leading-6 text-base-content/60">{{ $condition['evidence'] }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            <h4 class="mt-5 text-sm font-semibold tracking-wide text-base-content/70">盘面</h4>
            <div class="mt-3 space-y-3">
                @foreach ($trace['observations'] as $observation)
                    <div class="border-l-2 border-base-300 pl-4">
                        <strong class="block text-sm">{{ $observation['label'] }}</strong>
                        <p class="mt-1 text-sm leading-6 text-base-content/60">{{ $observation['evidence'] }}</p>
                    </div>
                @endforeach
            </div>

            <h4 class="mt-5 text-sm font-semibold tracking-wide text-base-content/70">本课尚未支持的判断项</h4>
            <p class="mt-1 text-xs leading-5 text-base-content/45">以下为原文提到、但本课尚未实现的判断项，不代表当前盘面已触发。</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm leading-6 text-base-content/55">
                @foreach ($trace['uncovered'] as $uncovered)
                    <li>{{ $uncovered }}</li>
                @endforeach
            </ul>
        </div>
    </div>

</section>
