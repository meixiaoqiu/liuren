<?php

use App\Livewire\Pan\CreatePan;
use App\Models\Pan;
use App\Services\PanCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('frontend pan page is available', function () {
    $this->get(route('pan.create'))->assertOk()->assertSee('大六壬排盘')->assertSee('设置起课信息')->assertSee('年命')->assertSee('行年')->assertSee('立即排盘')->assertSee('csrf-token');
});

test('frontend calculation keeps the deterministic core available without expert matches', function () {
    $expected = app(PanCalculator::class)->calculate('2024-08-11 14:00:00')->toArray();
    $expected = [...$expected, 'nianming' => 2, 'xingnian' => 4, 'xingnian_gan' => 0, 'context' => ['people' => [
        ['role' => 'querent', 'birth_datetime' => '1986-08-01T00:00', 'gender' => 'male', 'nianming' => 2, 'xingnian' => 4, 'xingnian_gan' => 0],
    ]]];
    session()->forget('pan');
    $recordsBefore = Pan::query()->count();
    $component = Livewire::test(CreatePan::class)->set('datetime', '2024-08-11T14:00')->call('calculate')
        ->assertHasNoErrors()->assertSet('pan', $expected)->assertSee('三传')->assertSee('四课')->assertSee('天地盘')->assertSee('解盘信息');
    expect(array_filter($component->get('ruleMatches'), static fn (array $match): bool => str_starts_with($match['code'], 'lesson.') || str_starts_with($match['code'], 'bifa.')))->toBe([])
        ->and(session()->has('pan'))->toBeFalse()->and(Pan::query()->count())->toBe($recordsBefore);
});

test('frontend validates datetime and birth boundaries', function () {
    Livewire::test(CreatePan::class)->set('datetime', 'not-a-date')->call('calculate')->assertHasErrors(['datetime'])->assertSet('pan', null);
    Livewire::test(CreatePan::class)->set('datetime', '1986-07-31T23:59')->set('birthDatetime', '1986-08-01T00:00')->call('calculate')->assertHasErrors(['birthDatetime']);
});

test('frontend persists reusable inputs and survives invalid query parameters', function () {
    Livewire::test(CreatePan::class)->assertSee('liuren.pan.inputs.v1', false)->assertSee('localStorage.setItem', false)->assertSee('localStorage.getItem', false);
    Livewire::withQueryParams(['datetime' => 'not-a-date'])->test(CreatePan::class)->assertHasErrors(['datetime'])->assertSet('pan', null)->assertSee('立即排盘');
});

test('frontend explains the public shehai selection process', function () {
    $expected = app(PanCalculator::class)->calculate('2000-05-18 03:24:00')->toArray();
    $trace = $expected['shehaiTrace'];
    expect($trace)->not->toBeNull()->and($trace['candidates'])->not->toBeEmpty()->and($trace['decision']['selected_branch'])->toBe($expected['sanchuan0']);
    Livewire::test(CreatePan::class)->set('datetime', '2000-05-18T03:24')->call('calculate')->assertHasNoErrors()->assertSee('涉害过程')->assertSee($trace['decision']['rule']);
});

test('frontend keeps core fanyin and transmission distinctions', function () {
    Livewire::test(CreatePan::class)->set('datetime', '2000-01-11T13:00')->call('calculate')->assertHasNoErrors()->assertSee('返吟')->assertDontSee('涉害课');
    Livewire::test(CreatePan::class)->set('datetime', '2000-01-08T13:39')->call('calculate')->assertHasNoErrors()->assertSee('返吟')->assertDontSee('重审课');
});

test('frontend accepts reproducible pan inputs from query parameters', function () {
    Livewire::withQueryParams(['datetime' => '2025-01-10T08:00', 'birth' => '1986-08-01T00:00', 'gender' => 'male'])
        ->test(CreatePan::class)->assertSet('datetime', '2025-01-10T08:00')->assertSet('birthDatetime', '1986-08-01T00:00')
        ->assertSet('gender', 'male')->assertHasNoErrors()->assertSet('pan.calculationTime', '2025-01-10 08:00:00');
});

test('frontend rejects a spouse with the same gender as the querent', function () {
    Livewire::test(CreatePan::class)->set('datetime', '2000-03-15T13:00')->set('birthDatetime', '1952-06-01T00:00')->set('gender', 'male')
        ->set('people', [['role' => 'spouse', 'birth_datetime' => '1967-06-01T00:00', 'gender' => 'male']])->call('calculate')->assertHasErrors(['people.0.gender']);
});

test('frontend limits the people array', function () {
    $max = (new ReflectionClass(CreatePan::class))->getConstant('MAX_PEOPLE');
    $oversize = array_fill(0, $max + 1, ['role' => 'other', 'birth_datetime' => '1980-01-01T00:00', 'gender' => 'male']);
    Livewire::test(CreatePan::class)->set('datetime', '2000-03-15T13:00')->set('birthDatetime', '1952-06-01T00:00')->set('gender', 'male')
        ->set('people', $oversize)->call('calculate')->assertHasErrors(['people']);
});

test('frontend rejects more than one spouse', function () {
    Livewire::test(CreatePan::class)->set('datetime', '2000-03-15T13:00')->set('birthDatetime', '1952-06-01T00:00')->set('gender', 'male')->set('people', [
        ['role' => 'spouse', 'birth_datetime' => '1967-06-01T00:00', 'gender' => 'female'],
        ['role' => 'spouse', 'birth_datetime' => '1970-01-01T00:00', 'gender' => 'female'],
    ])->call('calculate')->assertHasErrors(['people.1.role']);
});
