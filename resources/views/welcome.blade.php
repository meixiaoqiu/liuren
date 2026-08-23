<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-zinc-950 text-zinc-100 antialiased">
        <main class="mx-auto flex min-h-screen w-full max-w-5xl flex-col px-6 py-8">
            <nav class="flex items-center justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <span class="flex size-9 items-center justify-center rounded-md bg-amber-500 text-sm font-bold text-zinc-950">LR</span>
                    <span class="text-sm font-semibold tracking-wide">{{ config('app.name') }}</span>
                </a>

                <a href="{{ url('/admin') }}" class="rounded-md border border-amber-400/50 px-3 py-2 text-sm text-amber-200 transition hover:border-amber-300 hover:text-amber-100">Admin</a>
            </nav>

            <section class="grid flex-1 items-center gap-10 py-14 lg:grid-cols-[1.1fr_0.9fr]">
                <div>
                    <p class="mb-4 text-sm font-medium uppercase tracking-[0.25em] text-amber-300">Da Liu Ren</p>
                    <h1 class="max-w-3xl text-4xl font-semibold leading-tight text-white sm:text-5xl">Open-source Liuren pan creation and study tools.</h1>
                    <p class="mt-6 max-w-2xl text-base leading-7 text-zinc-300">
                        Liuren combines Laravel, Filament, and Livewire to create, store, and inspect Da Liu Ren pan records. The repository includes regression tests that protect core pan creation output during framework upgrades and refactors.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ url('/admin') }}" class="rounded-md bg-amber-400 px-4 py-2 text-sm font-semibold text-zinc-950 transition hover:bg-amber-300">Open Admin</a>
                    </div>
                </div>

                <div class="rounded-lg border border-zinc-800 bg-zinc-900/70 p-6 shadow-2xl shadow-black/30">
                    <h2 class="text-sm font-semibold uppercase tracking-[0.2em] text-zinc-400">Project checks</h2>
                    <dl class="mt-6 space-y-4 text-sm">
                        <div class="flex items-start justify-between gap-4 border-b border-zinc-800 pb-4">
                            <dt class="text-zinc-400">Core regression</dt>
                            <dd class="text-right font-medium text-zinc-100">PanResource golden fixtures</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4 border-b border-zinc-800 pb-4">
                            <dt class="text-zinc-400">Framework</dt>
                            <dd class="text-right font-medium text-zinc-100">Laravel 13 / Filament 5</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-zinc-400">License</dt>
                            <dd class="text-right font-medium text-zinc-100">MIT</dd>
                        </div>
                    </dl>
                </div>
            </section>
        </main>
    </body>
</html>
