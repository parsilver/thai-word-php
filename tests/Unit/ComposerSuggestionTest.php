<?php

use Farzai\ThaiWord\Composer;
use Farzai\ThaiWord\Exceptions\SegmentationException;
use Farzai\ThaiWord\Suggestions\Strategies\LevenshteinSuggestionStrategy;

describe('Composer with Suggestions', function () {
    beforeEach(function () {
        // Clear any existing instance
        Composer::clearInstance();
    });

    afterEach(function () {
        // Clear instance after each test
        Composer::clearInstance();
    });

    it('can enable suggestions via facade', function () {
        Composer::enableSuggestions(['threshold' => 0.7]);

        $config = Composer::getConfig();
        expect($config['enable_suggestions'])->toBeTrue()
            ->and($config['suggestion_threshold'])->toBe(0.7);
    });

    it('can disable suggestions via facade', function () {
        Composer::enableSuggestions();
        Composer::disableSuggestions();

        $config = Composer::getConfig();
        expect($config['enable_suggestions'])->toBeFalse();
    });

    it('can suggest words via facade', function () {
        Composer::enableSuggestions();

        $suggestions = Composer::suggest('สวัสด');

        expect($suggestions)->toBeArray()
            ->not->toBeEmpty();

        foreach ($suggestions as $suggestion) {
            expect($suggestion)->toHaveKeys(['word', 'score'])
                ->and($suggestion['word'])->toBeString()
                ->and($suggestion['score'])->toBeFloat();
        }
    });

    it('throws exception when suggesting without enabled suggestions', function () {
        expect(fn () => Composer::suggest('สวัสด'))
            ->toThrow(SegmentationException::class);
    });

    it('can segment with suggestions via facade', function () {
        Composer::enableSuggestions();

        $result = Composer::segmentWithSuggestions('สวัสดีครับ');

        expect($result)->toBeArray()
            ->not->toBeEmpty();

        foreach ($result as $item) {
            expect($item)->toHaveKey('word')
                ->and($item['word'])->toBeString();

            if (isset($item['suggestions'])) {
                expect($item['suggestions'])->toBeArray();
            }
        }
    });

    it('can set custom suggestion strategy via facade', function () {
        $customStrategy = new LevenshteinSuggestionStrategy;
        $customStrategy->setThreshold(0.8);

        Composer::setSuggestionStrategy($customStrategy);

        $strategy = Composer::getSuggestionStrategy();
        expect($strategy)->toBe($customStrategy)
            ->and($strategy->getThreshold())->toBe(0.8);
    });

    it('can get suggestion strategy via facade', function () {
        // Initially no strategy
        expect(Composer::getSuggestionStrategy())->toBeNull();

        // After enabling suggestions
        Composer::enableSuggestions();
        $strategy = Composer::getSuggestionStrategy();
        expect($strategy)->toBeInstanceOf(LevenshteinSuggestionStrategy::class);
    });

    it('can create segmenter with suggestion strategy', function () {
        $customStrategy = new LevenshteinSuggestionStrategy;

        $segmenter = Composer::create(null, null, $customStrategy);

        expect($segmenter->getSuggestionStrategy())->toBe($customStrategy);
    });

    it('facade maintains suggestion configuration between calls', function () {
        Composer::enableSuggestions(['threshold' => 0.75, 'max_suggestions' => 3]);

        // First call
        $config1 = Composer::getConfig();

        // Second call should maintain configuration
        $config2 = Composer::getConfig();

        expect($config1['suggestion_threshold'])->toBe($config2['suggestion_threshold'])
            ->and($config1['max_suggestions'])->toBe($config2['max_suggestions']);
    });

    it('can update suggestion config via facade', function () {
        Composer::enableSuggestions();

        Composer::updateConfig([
            'suggestion_threshold' => 0.9,
            'max_suggestions' => 2,
        ]);

        $config = Composer::getConfig();
        expect($config['suggestion_threshold'])->toBe(0.9)
            ->and($config['max_suggestions'])->toBe(2);
    });

    it('suggestion methods work with custom max suggestions', function () {
        Composer::enableSuggestions();

        $suggestions = Composer::suggest('สวัสด', 2);

        expect(count($suggestions))->toBeLessThanOrEqual(2);
    });

    it('facade clears suggestion strategy when set to null', function () {
        Composer::enableSuggestions();
        expect(Composer::getSuggestionStrategy())->not->toBeNull();

        Composer::setSuggestionStrategy(null);
        expect(Composer::getSuggestionStrategy())->toBeNull();

        $config = Composer::getConfig();
        expect($config['enable_suggestions'])->toBeFalse();
    });

    it('facade supports chaining configuration', function () {
        // Enable suggestions and immediately use them
        Composer::enableSuggestions(['threshold' => 0.8]);
        $result = Composer::segmentWithSuggestions('สวัสดีครับ');

        expect($result)->toBeArray()
            ->not->toBeEmpty();
    });

    it('facade handles empty text gracefully', function () {
        Composer::enableSuggestions();

        $result = Composer::segmentWithSuggestions('');
        expect($result)->toBeEmpty();

        $suggestions = Composer::suggest('');
        expect($suggestions)->toBeEmpty();
    });
});
