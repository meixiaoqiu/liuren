<?php

namespace App\Livewire\Pan;

use App\Services\PanCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CreatePan extends Component
{
    public string $datetime = '';

    public ?array $pan = null;

    public function mount(): void
    {
        $this->datetime = now('Asia/Shanghai')->format('Y-m-d\TH:i');
    }

    public function calculate(PanCalculator $calculator): void
    {
        $validated = $this->validate([
            'datetime' => ['required', 'date_format:Y-m-d\TH:i'],
        ]);

        $datetime = CarbonImmutable::createFromFormat(
            'Y-m-d\TH:i',
            $validated['datetime'],
            'Asia/Shanghai',
        )->format('Y-m-d H:i:s');

        $this->pan = $calculator->calculate($datetime)->toArray();
    }

    public function render(): View
    {
        return view('livewire.pan.create-pan', [
            'dizhi' => PanCalculator::$dizhi,
            'tiangan' => PanCalculator::$tiangan,
            'wuxing' => PanCalculator::$wuxing,
            'wuxingTian' => PanCalculator::$wuxingTian,
            'wuxingDi' => PanCalculator::$wuxingDi,
            'tianjiangNames' => PanCalculator::$tianjiang,
            'jiuzongmenNames' => PanCalculator::$jiuzongmen,
            'liuqinNames' => PanCalculator::$liuqin,
        ]);
    }
}
