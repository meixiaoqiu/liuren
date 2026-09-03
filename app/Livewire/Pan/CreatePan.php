<?php

namespace App\Livewire\Pan;

use App\Data\PanResult;
use App\Domain\Pan\FateCalculator;
use App\Domain\Pan\Rules\PanRuleEngine;
use App\Services\PanCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\Component;

class CreatePan extends Component
{
    /** @var list<string> */
    private const HIDDEN_RULE_CODES = [
        'sanchuan.tianpan_shunchuan',
    ];

    #[Url(as: 'datetime', history: true, keep: true)]
    public string $datetime = '';

    #[Url(as: 'birth', history: true, keep: true)]
    public string $birthDatetime = '1986-08-01T00:00';

    #[Url(as: 'gender', history: true, keep: true)]
    public string $gender = 'male';

    public ?array $pan = null;

    /** @var array<int, array{code: string, name: string, group: string, description: string, marker: string, gua: ?string, guaSymbol: ?string, xiang: ?string, evidence: array<string, mixed>, coverageAreas: list<string>}> */
    public array $ruleMatches = [];

    /** @var list<string> */
    public array $coverageNotices = [];

    public function mount(
        PanCalculator $calculator,
        PanRuleEngine $ruleEngine,
        FateCalculator $fateCalculator,
    ): void {
        if ($this->datetime === '') {
            $this->datetime = now('Asia/Shanghai')->format('Y-m-d\TH:i');
        }

        if (request()->query->has('datetime')) {
            try {
                $this->calculate($calculator, $ruleEngine, $fateCalculator);
            } catch (ValidationException $exception) {
                // URL 参数可能由用户手工修改；保留表单和验证错误供其修正。
                $this->setErrorBag($exception->validator->errors());
            }
        }
    }

    public function calculate(
        PanCalculator $calculator,
        PanRuleEngine $ruleEngine,
        FateCalculator $fateCalculator,
    ): void {
        $validated = $this->validate([
            'datetime' => ['required', 'date_format:Y-m-d\TH:i'],
            'birthDatetime' => ['required', 'date_format:Y-m-d\TH:i', 'before_or_equal:datetime'],
            'gender' => ['required', 'in:male,female'],
        ]);

        $datetime = CarbonImmutable::createFromFormat(
            'Y-m-d\TH:i',
            $validated['datetime'],
            'Asia/Shanghai',
        )->format('Y-m-d H:i:s');
        $birthDatetime = CarbonImmutable::createFromFormat(
            'Y-m-d\TH:i',
            $validated['birthDatetime'],
            'Asia/Shanghai',
        )->format('Y-m-d H:i:s');

        $calculated = $calculator->calculate($datetime);
        $birthPan = $calculator->calculate($birthDatetime);
        $fate = $fateCalculator->calculate(
            $birthPan->get('nianzhi'),
            $calculated->get('nianzhi'),
            $validated['gender'],
        );
        $result = new PanResult([
            ...$calculated->toArray(),
            ...$fate,
        ]);

        $this->pan = $result->toArray();
        $visibleMatches = array_filter(
            $ruleEngine->evaluate($result),
            fn ($match): bool => ! in_array($match->code, self::HIDDEN_RULE_CODES, true),
        );

        usort(
            $visibleMatches,
            fn ($left, $right): int => ($left->marker === '课' ? 0 : 1) <=> ($right->marker === '课' ? 0 : 1),
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
            'genderOptions' => [
                ['id' => 'male', 'name' => '男'],
                ['id' => 'female', 'name' => '女'],
            ],
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
