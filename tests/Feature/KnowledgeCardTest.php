<?php

use App\Domain\Pan\BiFa\Rules\LianMuGuiRenRule;
use App\Domain\Pan\BiFa\Rules\QianHouYinCongRule;
use App\Domain\Pan\Facts\PanFacts;
use App\Services\PanCalculator;
use App\Support\BiFaCatalog;
use App\Support\Knowledge\BiFaKnowledgeCardFactory;
use App\Support\Knowledge\KnowledgeCard;

test('KnowledgeCard can be generated from the first bifa match without internal identifiers', function () {
    $pan = (new PanCalculator)->calculate('2000-01-23 13:00:00');
    $match = (new QianHouYinCongRule)->match(PanFacts::from($pan));

    expect($match)->not->toBeNull();

    $card = app(BiFaKnowledgeCardFactory::class)->fromMatch($match);
    $contents = json_encode($card->toArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

    expect($card)->toBeInstanceOf(KnowledgeCard::class)
        ->and($card->type)->toBe('bifa')
        ->and($card->typeLabel)->toBe('毕法')
        // 排盘块不展示法序号，仅由 fromDetail() 注入
        ->and($card->label)->toBe('')
        ->and($card->title)->toBe('前后引从升迁吉')
        ->and($card->conditions)->toHaveCount(10)
        ->and(array_unique(array_column($card->conditions, 'marker')))->toBe(['⏺'])
        ->and($contents)->toContain('引从天干')
        ->and($contents)->not->toContain('bifa.01')
        ->and($contents)->not->toContain('yin_gan')
        ->and($contents)->not->toContain('matched_routes')
        ->and($contents)->not->toContain('QianHouYinCongRule');
});

test('KnowledgeCard fromDetail provides 第 N 法 label as the user-facing numbering', function () {
    $pan = (new PanCalculator)->calculate('2000-01-23 13:00:00');
    $match = (new QianHouYinCongRule)->match(PanFacts::from($pan));
    $law = BiFaCatalog::findByCode($match->code);
    expect($law)->not->toBeNull();

    $definition = [
        'description' => '测试用定义。',
        'foundations' => [],
    ];

    $card = app(BiFaKnowledgeCardFactory::class)->fromDetail($law, $definition);

    expect($card->label)->toBe('第 1 法')
        ->and($card->title)->toBe('前后引从升迁吉')
        ->and($card->type)->toBe('bifa');
});

test('third bifa KnowledgeCard exposes Chinese routes and hides implementation names', function () {
    $pan = (new PanCalculator)->calculate('2031-01-01 03:00:00');
    $match = (new LianMuGuiRenRule)->match(PanFacts::from($pan));
    expect($match)->not->toBeNull();
    $card = app(BiFaKnowledgeCardFactory::class)->fromMatch($match);
    $payload = json_encode($card->toArray(), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    expect($card->conditions)->toHaveCount(8)
        ->and($payload)->toContain('亚魁临干年命')
        ->and($payload)->not->toContain('bifa.03')
        ->and($payload)->not->toContain('ya_kui_you_on_stem_or_fate')
        ->and($payload)->not->toContain('matched_routes');
});
