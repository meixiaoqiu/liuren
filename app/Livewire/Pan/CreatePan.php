<?php

namespace App\Livewire\Pan;

use App\Data\PanResult;
use App\Domain\Pan\Facts\PanFacts;
use App\Domain\Pan\FateCalculator;
use App\Domain\Pan\Rules\PanRuleEngine;
use App\Services\PanCalculator;
use App\Support\KeJingCatalog;
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

    /**
     * people 数组的最大元素数。
     *
     * 实际角色集合（spouse/father/mother/child/partner/other）共 6 种，
     * 本上限对「夫妻 + 双方父母 + 多个子女 + 合作方」等合理场景留出充足余量；
     * 同时防止公开 URL/Livewire 请求构造大数组触发 N 次完整 PanCalculator 计算。
     */
    private const MAX_PEOPLE = 10;

    #[Url(as: 'datetime', history: true, keep: true)]
    public string $datetime = '';

    #[Url(as: 'birth', history: true, keep: true)]
    public string $birthDatetime = '1986-08-01T00:00';

    #[Url(as: 'gender', history: true, keep: true)]
    public string $gender = 'male';

    /** @var list<array{role: string, birth_datetime: string, gender: string}> 相关人物（不含占者本人）。 */
    #[Url(as: 'people', history: true, keep: true)]
    public array $people = [];

    /**
     * 课经白名单中的案例 ID。用于在排盘页上展示「原文参考盘·尚未覆盖」提示。
     * 排盘页必须通过 `KeJingCatalog::findReferenceCase()` 二次校验，
     * 不得直接根据本查询参数渲染 reference_only 提示。
     */
    #[Url(as: 'reference_case', history: false, keep: false)]
    public string $referenceCaseId = '';

    public ?array $pan = null;

    /** @var array<int, array{code: string, name: string, group: string, description: string, marker: string, gua: ?string, guaSymbol: ?string, xiang: ?string, evidence: array<string, mixed>, coverageAreas: list<string>}> */
    public array $ruleMatches = [];

    /** @var list<string> */
    public array $coverageNotices = [];

    /**
     * 经白名单校验后的参考案例元数据。仅当查询参数中的 reference_case
     * 能在 KeJingCatalog 中找到且确为 reference_only 时才填充；
     * 普通盘面（包括命中旺孕格的盘）此项始终为 null。
     *
     * @var array{case: array<string, mixed>, lesson: array<string, mixed>}|null
     */
    public ?array $referenceCase = null;

    /** @var list<array{code: string, name: string, notice: string}> */
    public array $notEvaluated = [];

    /** @var array{key: string, name: string, wang: int, xiang: int, starts_at: string, ends_at: string, implementation: string}|null */
    public ?array $seasonalPeriod = null;

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

    public function addPerson(): void
    {
        if (count($this->people) >= self::MAX_PEOPLE) {
            return;
        }

        // 配偶角色唯一：已有配偶时改添加「其他」人物，避免重复配偶。
        $role = in_array('spouse', array_column($this->people, 'role'), true) ? 'other' : 'spouse';
        $this->people[] = [
            'role' => $role,
            'birth_datetime' => '',
            'gender' => $role === 'spouse' ? ($this->gender === 'male' ? 'female' : 'male') : 'male',
        ];
    }

    public function removePerson(int $index): void
    {
        unset($this->people[$index]);
        $this->people = array_values($this->people);
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
            'people' => ['array', 'max:'.self::MAX_PEOPLE],
            'people.*.role' => ['required', 'in:spouse,father,mother,child,partner,other'],
            'people.*.birth_datetime' => ['required', 'date_format:Y-m-d\TH:i', 'before_or_equal:datetime'],
            'people.*.gender' => ['required', 'in:male,female'],
        ]);

        $spouseCount = 0;
        foreach ($validated['people'] ?? [] as $index => $person) {
            if ($person['role'] === 'spouse') {
                $spouseCount++;
                if ($spouseCount > 1) {
                    throw ValidationException::withMessages([
                        "people.$index.role" => 'spouse 角色至多出现一次。',
                    ]);
                }
                if ($person['gender'] === $validated['gender']) {
                    throw ValidationException::withMessages([
                        "people.$index.gender" => '配偶性别应与占者性别不同。',
                    ]);
                }
            }
        }

        $datetimeCarbon = CarbonImmutable::createFromFormat(
            'Y-m-d\TH:i',
            $validated['datetime'],
            'Asia/Shanghai',
        );
        $birthCarbon = CarbonImmutable::createFromFormat(
            'Y-m-d\TH:i',
            $validated['birthDatetime'],
            'Asia/Shanghai',
        );
        $datetime = $datetimeCarbon->format('Y-m-d H:i:s');
        $birthDatetime = $birthCarbon->format('Y-m-d H:i:s');

        $calculated = $calculator->calculate($datetime);
        $birthPan = $calculator->calculate($birthDatetime);
        $fate = $fateCalculator->calculate(
            $birthPan->get('nian_index'),
            $calculated->get('nian_index'),
            $validated['gender'],
        );

        $people = [
            [
                'role' => 'querent',
                'birth_datetime' => $validated['birthDatetime'],
                'gender' => $validated['gender'],
                'nianming' => $fate['nianming'],
                'xingnian' => $fate['xingnian'],
                'xingnian_gan' => $fate['xingnian_gan'],
            ],
        ];

        foreach ($validated['people'] ?? [] as $person) {
            $personBirthCarbon = CarbonImmutable::createFromFormat(
                'Y-m-d\TH:i',
                $person['birth_datetime'],
                'Asia/Shanghai',
            );
            $personBirthPan = $calculator->calculate($personBirthCarbon->format('Y-m-d H:i:s'));
            $personFate = $fateCalculator->calculate(
                $personBirthPan->get('nian_index'),
                $calculated->get('nian_index'),
                $person['gender'],
            );
            $people[] = [
                'role' => $person['role'],
                'birth_datetime' => $person['birth_datetime'],
                'gender' => $person['gender'],
                'nianming' => $personFate['nianming'],
                'xingnian' => $personFate['xingnian'],
                'xingnian_gan' => $personFate['xingnian_gan'],
            ];
        }

        $result = new PanResult([
            ...$calculated->toArray(),
            ...$fate,
            'context' => [
                'people' => $people,
            ],
        ]);

        $this->pan = $result->toArray();
        $visibleMatches = array_filter(
            $ruleEngine->evaluate($result),
            fn ($match): bool => ! in_array($match->code, self::HIDDEN_RULE_CODES, true),
        );

        usort(
            $visibleMatches,
            fn ($left, $right): int => ($left->isPrimary ? 0 : 1) <=> ($right->isPrimary ? 0 : 1),
        );

        $this->ruleMatches = array_values(array_map(
            fn ($match): array => $match->toArray(),
            $visibleMatches,
        ));
        $this->coverageNotices = $ruleEngine->coverageNotices($result);
        $this->referenceCase = $this->resolveReferenceCase();
        $this->notEvaluated = $ruleEngine->notEvaluated($result);
        $this->seasonalPeriod = PanFacts::from($result)->seasonalPeriod();
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
            'personRoleOptions' => [
                ['id' => 'spouse', 'name' => '配偶'],
                ['id' => 'father', 'name' => '父亲'],
                ['id' => 'mother', 'name' => '母亲'],
                ['id' => 'child', 'name' => '子女'],
                ['id' => 'partner', 'name' => '合作方'],
                ['id' => 'other', 'name' => '其他'],
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

    /**
     * 根据查询参数中的 reference_case 解析参考案例，并严格校验当前盘面参数。
     *
     * 校验通过的全部条件（任一不满足即返回 null）：
     *  - `reference_case` 非空；
     *  - `KeJingCatalog::findReferenceCase()` 能定位到一个确为 `reference_only` 的案例；
     *  - 当前 `datetime`、`birthDatetime`、`gender` 与该案例完全一致；
     *  - 当前 `people` 数组按 (role, birth_datetime, gender) 规范化后与该案例的 people 集合相等。
     *
     * 这样可以阻止「把有效 ID 附加到任意排盘 URL」以及「进入参考案例后修改参数」两种滥用路径。
     *
     * @return array{case: array<string, mixed>, lesson: array<string, mixed>}|null
     */
    private function resolveReferenceCase(): ?array
    {
        if ($this->referenceCaseId === '') {
            return null;
        }

        $found = KeJingCatalog::findReferenceCase($this->referenceCaseId);
        if ($found === null) {
            return null;
        }

        if (! $this->caseMatchesInputs($found['case'])) {
            return null;
        }

        return $found;
    }

    /**
     * 当前 Livewire 输入是否与目录案例的 datetime / birth / gender / people 完全一致。
     * people 比较为按 (role, birth_datetime, gender) 排序后逐项对比，对查询参数形式差异稳健。
     */
    private function caseMatchesInputs(array $case): bool
    {
        if (($this->datetime ?? '') !== ($case['datetime'] ?? '')) {
            return false;
        }

        if (($this->birthDatetime ?? '') !== ($case['birth'] ?? '')) {
            return false;
        }

        if (($this->gender ?? '') !== ($case['gender'] ?? '')) {
            return false;
        }

        return $this->normalizePeople($this->people) === $this->normalizePeople($case['people'] ?? []);
    }

    /**
     * @param  list<array<string, mixed>>  $people
     * @return list<array{role: string, birth_datetime: string, gender: string}>
     */
    private function normalizePeople(array $people): array
    {
        $normalized = array_map(
            fn (array $person): array => [
                'role' => (string) ($person['role'] ?? ''),
                'birth_datetime' => (string) ($person['birth_datetime'] ?? ''),
                'gender' => (string) ($person['gender'] ?? ''),
            ],
            $people,
        );

        usort(
            $normalized,
            fn (array $a, array $b): int => [$a['role'], $a['birth_datetime'], $a['gender']] <=> [$b['role'], $b['birth_datetime'], $b['gender']],
        );

        return $normalized;
    }
}
