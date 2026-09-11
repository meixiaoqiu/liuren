<header class="pan-classical-header sticky top-0 z-10 border-b border-base-content/10 backdrop-blur">
    <div class="flex items-center px-6 py-3">
        <a href="{{ route('pan.create') }}" class="flex items-center gap-3" wire:navigate>
            <div class="grid size-10 place-items-center rounded-xl bg-primary text-lg font-semibold text-primary-content shadow-sm">壬</div>
            <div>
                <div class="text-base font-semibold tracking-wide">大六壬排盘</div>
                <div class="text-xs text-base-content/55">以时起课 · 北京时间</div>
            </div>
        </a>
        <div class="ml-auto flex items-center">
            <x-menu horizontal activate-by-route active-bg-color="text-primary">
                <x-menu-item title="排盘" route="pan.create" />
                <x-menu-item title="课经" route="kejing" />
                <x-menu-item title="速查" route="reference" />
            </x-menu>
        </div>
    </div>
</header>
