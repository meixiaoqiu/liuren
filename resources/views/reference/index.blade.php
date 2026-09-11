<!DOCTYPE html>
<html lang="zh-CN" data-theme="liuren">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>速查 · 大六壬排盘</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-base-200 text-base-content antialiased">
        <div class="pan-classical-shell">
            @include('partials.pan-header')

            <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
                <header class="mb-6">
                    <h1 class="text-2xl font-semibold tracking-wide sm:text-3xl">速查</h1>
                    <p class="mt-2 text-sm leading-6 text-base-content/55">大六壬常用基础规则备忘。以项目现行计算口径为准，便于排盘与课经开发时快速核对。</p>
                    <p class="mt-1 text-sm leading-6 text-base-content/55">本页为开发与排盘时的基础规则速查；课体成立条件仍以课经及程序规则为准。</p>
                </header>

                <nav class="mb-8 flex flex-wrap gap-2" aria-label="页面内导航">
                    @foreach (['分类' => 'categories', '干支' => 'stems-branches', '五行' => 'elements', '寄宫' => 'lodgings', '地支关系' => 'relations', '旺衰' => 'prosperity', '旬空' => 'voids', '月将' => 'month-generals', '天将' => 'heavenly-generals'] as $label => $anchor)
                        <a href="#{{ $anchor }}" class="rounded-lg bg-base-100 px-3 py-2 text-sm font-medium shadow-sm transition hover:text-primary">{{ $label }}</a>
                    @endforeach
                </nav>

                <div class="flex flex-col gap-6">
                    <section id="categories" class="scroll-mt-24">
                        <x-card title="四孟、四仲、四季" shadow class="pan-data-card">
                            <div class="grid gap-3 sm:grid-cols-3">
                                @foreach ($reference::categories() as $group)
                                    <div class="bg-base-200/45 px-4 py-3"><strong>{{ $group['name'] }}</strong><span class="ml-3 font-semibold text-primary">{{ implode('　', $group['branches']) }}</span></div>
                                @endforeach
                            </div>
                        </x-card>
                    </section>

                    <section id="stems-branches" class="scroll-mt-24 grid gap-6 lg:grid-cols-2">
                        @foreach (['天干阴阳五行' => $reference::stems(), '地支阴阳五行' => $reference::branches()] as $title => $rows)
                            <x-card :title="$title" shadow class="pan-data-card">
                                <div class="overflow-x-auto"><table class="table table-sm"><thead><tr><th>干支</th><th>阴阳</th><th>五行</th></tr></thead><tbody>
                                    @foreach ($rows as $row)<tr><th class="text-primary">{{ $row['name'] }}</th><td>{{ $row['polarity'] }}</td><td>{{ $row['element'] }}</td></tr>@endforeach
                                </tbody></table></div>
                            </x-card>
                        @endforeach
                    </section>

                    <section id="elements" class="scroll-mt-24 grid gap-6 lg:grid-cols-2">
                        <x-card title="五行生克" shadow class="pan-data-card">
                            <p class="text-sm text-base-content/55">相生</p><p class="mt-2 font-semibold text-primary">{{ implode(' → ', $reference::generatingCycle()) }}</p>
                            <p class="mt-5 text-sm text-base-content/55">相克</p><p class="mt-2 font-semibold text-primary">{{ implode(' → ', $reference::overcomingCycle()) }}</p>
                        </x-card>
                        <x-card title="十干五合" shadow class="pan-data-card"><div class="flex flex-wrap gap-3">@foreach ($reference::stemCombinations() as $pair)<span class="bg-base-200/60 px-3 py-2 font-semibold text-primary">{{ $pair }}合</span>@endforeach</div></x-card>
                    </section>

                    <section id="lodgings" class="scroll-mt-24">
                        <x-card title="十干寄宫" shadow class="pan-data-card"><div class="grid grid-cols-2 gap-2 sm:grid-cols-5">@foreach ($reference::stemLodgings() as $item)<div class="bg-base-200/45 px-3 py-2 text-center"><strong class="text-primary">{{ $item['stem'] }}</strong>寄{{ $item['branch'] }}</div>@endforeach</div></x-card>
                    </section>

                    <section id="relations" class="scroll-mt-24">
                        <h2 class="mb-3 text-xl font-semibold">地支关系</h2>
                        <div class="grid items-start gap-4 md:grid-cols-2 lg:grid-cols-3">
                            @foreach (['六合' => $reference::liuhe(), '六冲' => $reference::clashes(), '六害' => $reference::harms(), '六破' => $reference::breaks()] as $title => $pairs)
                                <x-card :title="$title" shadow class="pan-data-card"><div class="flex flex-wrap gap-2">@foreach ($pairs as $pair)<x-badge :value="$pair" class="badge-primary badge-soft font-semibold" />@endforeach</div></x-card>
                            @endforeach
                            <x-card title="三合" shadow class="pan-data-card"><div class="space-y-2">@foreach ($reference::sanhe() as $group)<div><strong class="text-primary">{{ $group['branches'] }}</strong><span class="ml-3">{{ $group['element'] }}</span></div>@endforeach</div></x-card>
                            <x-card title="刑" shadow class="pan-data-card"><div class="space-y-4">@foreach ($reference::punishments() as $group)<div><p class="text-sm text-base-content/55">{{ $group['label'] }}</p><p class="mt-1 font-semibold text-primary">{{ implode('　', $group['relations']) }}</p></div>@endforeach</div></x-card>
                        </div>
                    </section>

                    <section id="prosperity" class="scroll-mt-24">
                        <x-card title="旺相休囚死" shadow class="pan-data-card"><div class="overflow-x-auto"><table class="table"><thead><tr><th>时令</th><th>旺</th><th>相</th><th>休</th><th>囚</th><th>死</th></tr></thead><tbody>@foreach ($reference::prosperity() as $row)<tr><th>{{ $row['season'] }} @isset($row['note'])<span class="block whitespace-nowrap text-xs font-normal text-base-content/45">{{ $row['note'] }}</span>@endisset</th><td>{{ $row['旺'] }}</td><td>{{ $row['相'] }}</td><td>{{ $row['休'] }}</td><td>{{ $row['囚'] }}</td><td>{{ $row['死'] }}</td></tr>@endforeach</tbody></table></div></x-card>
                    </section>

                    <section id="voids" class="scroll-mt-24">
                        <x-card title="六旬旬空" shadow class="pan-data-card"><div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">@foreach ($reference::voids() as $row)<div class="bg-base-200/45 px-4 py-3"><strong>{{ $row['旬'] }}</strong><span class="ml-3 text-primary">{{ $row['空'] }}</span></div>@endforeach</div></x-card>
                    </section>

                    <section id="month-generals" class="scroll-mt-24">
                        <x-card title="十二月将" shadow class="pan-data-card"><div class="overflow-x-auto"><table class="table"><thead><tr><th>地支</th><th>月将</th><th>节气范围</th></tr></thead><tbody>@foreach ($reference::monthGenerals() as $row)<tr><th class="text-primary">{{ $row['branch'] }}</th><td class="font-semibold">{{ $row['name'] }}</td><td class="whitespace-nowrap">{{ $row['range'] }}</td></tr>@endforeach</tbody></table></div></x-card>
                    </section>

                    <section id="heavenly-generals" class="scroll-mt-24">
                        <x-card title="十二天将顺序" shadow class="pan-data-card"><div class="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-6">@foreach ($reference::heavenlyGenerals() as $general)<div class="bg-base-200/45 px-3 py-2 text-center font-semibold text-primary">{{ $general }}</div>@endforeach</div></x-card>
                    </section>
                </div>
            </main>
        </div>
    </body>
</html>
