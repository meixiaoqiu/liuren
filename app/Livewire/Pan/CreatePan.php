<?php

namespace App\Livewire\Pan;

use App\Domain\Pan\Rules\PanRuleEngine;
use App\Services\PanCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CreatePan extends Component
{
    /** @var list<string> */
    private const HIDDEN_RULE_CODES = [
        'sanchuan.tianpan_shunchuan',
    ];

    public string $datetime = '';

    public ?array $pan = null;

    /** @var array<int, array{code: string, name: string, group: string, description: string, marker: string, evidence: array<string, mixed>, coverageAreas: list<string>}> */
    public array $ruleMatches = [];

    /** @var list<string> */
    public array $coverageNotices = [];

    public function mount(): void
    {
        $this->datetime = now('Asia/Shanghai')->format('Y-m-d\TH:i');
    }

    public function calculate(PanCalculator $calculator, PanRuleEngine $ruleEngine): void
    {
        $validated = $this->validate([
            'datetime' => ['required', 'date_format:Y-m-d\TH:i'],
        ]);

        $datetime = CarbonImmutable::createFromFormat(
            'Y-m-d\TH:i',
            $validated['datetime'],
            'Asia/Shanghai',
        )->format('Y-m-d H:i:s');

        $result = $calculator->calculate($datetime);

        $this->pan = $result->toArray();
        $visibleMatches = array_filter(
            $ruleEngine->evaluate($result),
            fn ($match): bool => ! in_array($match->code, self::HIDDEN_RULE_CODES, true),
        );

        $this->ruleMatches = array_values(array_map(
            fn ($match): array => $match->toArray(),
            $visibleMatches,
        ));
        $this->coverageNotices = $ruleEngine->coverageNotices($result);
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
            'lessonInterpretations' => $this->ruleMatches,
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
}
