<?php

use App\Domain\Pan\FateCalculator;

test('male annual fate starts at yin and advances with the year branch', function () {
    $fate = (new FateCalculator)->calculate(3, 10, 'male');

    expect($fate)->toBe(['nianming' => 3, 'xingnian' => 9]);
});

test('female annual fate starts at shen and moves backward with the year branch', function () {
    $fate = (new FateCalculator)->calculate(3, 10, 'female');

    expect($fate)->toBe(['nianming' => 3, 'xingnian' => 1]);
});
