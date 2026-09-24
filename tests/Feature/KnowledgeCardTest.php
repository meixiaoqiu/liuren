<?php

use App\Domain\Pan\BiFa\Rules\QianHouYinCongRule;
use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;
use App\Support\Knowledge\BiFaKnowledgeCardFactory;
use App\Support\Knowledge\KnowledgeCard;

test('KnowledgeCard can be generated from the first bifa match without internal identifiers', function () {
    $pan = (new PanCalculator)->calculate('2000-01-23 13:00:00');
    $match = (new QianHouYinCongRule)->match(PanFacts::from($pan));

    expect($match)->not->toBeNull();

    $card = app(BiFaKnowledgeCardFactory::class)->fromMatch($match);
    $contents = json_encode($card->toArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

    expect($card)->toBeInstanceOf(KnowledgeCard::class)
        ->and($card->type)->toBe('毕法')
        ->and($card->code)->toBe('第 1 法')
        ->and($card->title)->toBe('前后引从升迁吉')
        ->and($card->conditions)->toHaveCount(10)
        ->and(array_unique(array_column($card->conditions, 'marker')))->toBe(['⏺'])
        ->and($contents)->toContain('引从天干')
        ->and($contents)->not->toContain('bifa.01')
        ->and($contents)->not->toContain('yin_gan')
        ->and($contents)->not->toContain('matched_routes')
        ->and($contents)->not->toContain('QianHouYinCongRule');
});
