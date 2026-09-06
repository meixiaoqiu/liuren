<?php

use App\Support\KeJingCatalog;

test('kejing page lists every lesson with its hexagram', function () {
    $response = $this->get(route('kejing'))
        ->assertOk()
        ->assertSee('课经');

    foreach (KeJingCatalog::lessons() as $lesson) {
        $response
            ->assertSee($lesson['name'])
            ->assertSee($lesson['gua'].'卦');

        expect($lesson['cases'])->not->toBeEmpty();
    }
});

test('every kejing case link reproduces its lesson on the pan page', function () {
    foreach (KeJingCatalog::lessons() as $lesson) {
        foreach ($lesson['cases'] as $case) {
            $this->get(route('pan.create', [
                'datetime' => $case['datetime'],
                'birth' => $case['birth'],
                'gender' => $case['gender'],
            ]))
                ->assertOk()
                ->assertSee($lesson['name']);
        }
    }
});

test('every kejing case declares its selection reason', function () {
    foreach (KeJingCatalog::lessons() as $lesson) {
        foreach ($lesson['cases'] as $case) {
            expect($case['reason'])->not->toBeEmpty();
        }
    }
});

test('yincong catalog covers all seven textual structures and both noble directions', function () {
    $yincong = collect(KeJingCatalog::lessons())
        ->firstWhere('code', 'lesson.yincong');

    expect($yincong)->not->toBeNull();

    $labels = implode('', array_column($yincong['cases'], 'label'));

    expect($yincong['cases'])->toHaveCount(8)
        ->and($labels)
        ->toContain('拱天干', '拱地支', '两贵引从', '贵临干支拱年命', '干支拱日禄', '干支拱夜贵', '干支拱昼贵', '反向');
});

test('hengtong catalog covers all five grids and both di-sheng directions', function () {
    $hengtong = collect(KeJingCatalog::lessons())
        ->firstWhere('code', 'lesson.hengtong');

    expect($hengtong)->not->toBeNull();

    $labels = implode('', array_column($hengtong['cases'], 'label'));

    expect($hengtong['cases'])->toHaveCount(7)
        ->and($labels)
        ->toContain('用神生日', '递生格', '俱生格', '互生格', '互旺格', '俱旺格', '逆');
});
