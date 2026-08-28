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
            'jigong' => PanCalculator::$jigong,
            'tianjiangNames' => PanCalculator::$tianjiang,
            'liuqinNames' => PanCalculator::$liuqin,
            'xundunLabels' => $this->xundunLabels(),
            'lessonInterpretations' => $this->lessonInterpretations(),
        ]);
    }

    /** @return array<int, string> */
    private function xundunLabels(): array
    {
        if ($this->pan === null) {
            return [];
        }

        $dayIndex = array_search(
            [$this->pan['rigan'], $this->pan['rizhi']],
            PanCalculator::$jiazi2Ganzhi,
            true,
        );
        $xunFirstZhi = [0, 10, 8, 6, 4, 2][intdiv($dayIndex, 10)];

        return array_map(
            fn (int $branch): string => PanCalculator::$tiangan[($branch - $xunFirstZhi + 12) % 12],
            array_keys(PanCalculator::$dizhi),
        );
    }

    /**
     * Keep interpretation content as a collection so one pan can display
     * multiple lessons without changing the presentation structure later.
     *
     * @return array<int, array{name: string, description: string}>
     */
    private function lessonInterpretations(): array
    {
        if ($this->pan === null) {
            return [];
        }

        $lesson = $this->pan['jiuzongmen'];
        $descriptions = [
            0 => '当前盘面尚未归入明确课体。',
            1 => '四课中只有一处上克下，取克下之上神发用，顺取中传与末传。',
            2 => '四课中只有一处下贼上，取受贼之上神发用，顺取中传与末传。',
            3 => '四课出现多个下贼上时，取与日干阴阳相比者发用。',
            4 => '四课无下贼上而有多个上克下时，取与日干阴阳相比者发用。',
            5 => '克贼不止一处且比用仍不能唯一取用时，比较各课涉害深浅，以涉害较深者发用。',
            6 => '涉害深浅相等时，候选课临四孟，取孟上神发用。',
            7 => '涉害深浅相等且不临四孟时，候选课临四仲，取仲上神发用。',
            8 => '涉害深浅相等且不临孟仲时，依日干阴阳从干上神或支上神取用。',
            9 => '四课无直接克贼，而有上神遥克日干，取遥克日干之神发用。',
            10 => '四课无直接克贼，而日干遥克上神，取被日干遥克之神发用。',
            11 => '四课无克贼、无遥克且四课不重复，阳日从地盘酉上神发用。',
            12 => '四课无克贼、无遥克且四课不重复，阴日从天盘酉下神发用。',
            13 => '四课不全而又不成八专时，阳日取干合上神，阴日取支前三合神发用。',
            14 => '干支同位、四课实际只有两课且无克贼时，按日干阴阳进退取初传。',
            15 => '八专课中三传归于同一神，形成独足之象。',
            16 => '天地盘伏吟而四课有克贼，先依克贼取初传，再依刑冲取中末传。',
            17 => '天地盘伏吟、四课无克贼且为阳日，从干上神发用。',
            18 => '天地盘伏吟、四课无克贼且为阴日，从支上神发用。',
            19 => '伏吟无克贼而初传自刑，改从干支另一上神续取中末传。',
            20 => '天地盘反吟，三传主要依冲神递取。',
            21 => '反吟中的特殊日课，不依一般反吟次序，另从规定神位取三传。',
        ];

        return [[
            'name' => PanCalculator::$jiuzongmen[$lesson],
            'description' => $descriptions[$lesson],
        ]];
    }
}
