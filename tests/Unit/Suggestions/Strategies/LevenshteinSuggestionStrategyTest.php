<?php

use Farzai\ThaiWord\Contracts\DictionaryInterface;
use Farzai\ThaiWord\Exceptions\SegmentationException;
use Farzai\ThaiWord\Suggestions\Strategies\LevenshteinSuggestionStrategy;

describe('LevenshteinSuggestionStrategy', function () {
    beforeEach(function () {
        $this->strategy = new LevenshteinSuggestionStrategy;
        $this->mockDictionary = Mockery::mock(DictionaryInterface::class);
    });

    afterEach(function () {
        Mockery::close();
    });

    it('can calculate similarity between identical words', function () {
        $similarity = $this->strategy->calculateSimilarity('สวัสดี', 'สวัสดี');

        expect($similarity)->toBe(1.0);
    });

    it('can calculate similarity between different words', function () {
        $similarity = $this->strategy->calculateSimilarity('สวัสดี', 'สวัสด');

        expect($similarity)->toBeGreaterThan(0.8)
            ->toBeLessThan(1.0);
    });

    it('can calculate similarity between completely different words', function () {
        $similarity = $this->strategy->calculateSimilarity('สวัสดี', 'ขอบคุณ');

        expect($similarity)->toBeLessThan(0.3);
    });

    it('can set and get threshold', function () {
        $this->strategy->setThreshold(0.8);

        expect($this->strategy->getThreshold())->toBe(0.8);
    });

    it('throws exception for invalid threshold', function () {
        expect(fn () => $this->strategy->setThreshold(1.5))
            ->toThrow(SegmentationException::class);
    });

    it('throws exception for negative threshold', function () {
        expect(fn () => $this->strategy->setThreshold(-0.1))
            ->toThrow(SegmentationException::class);
    });

    it('can set max word length diff', function () {
        $this->strategy->setMaxWordLengthDiff(5);

        expect($this->strategy->getMaxWordLengthDiff())->toBe(5);
    });

    it('can get cache stats', function () {
        $stats = $this->strategy->getCacheStats();

        expect($stats)->toBeArray()
            ->toHaveKeys(['cache_size', 'max_cache_size', 'memory_usage_mb']);
    });

    it('can clear cache', function () {
        // Perform some calculations to populate cache
        $this->strategy->calculateSimilarity('สวัสดี', 'สวัสด');
        $this->strategy->calculateSimilarity('ครับ', 'ครบ');

        $statsBeforeClear = $this->strategy->getCacheStats();
        expect($statsBeforeClear['cache_size'])->toBeGreaterThan(0);

        $this->strategy->clearCache();

        $statsAfterClear = $this->strategy->getCacheStats();
        expect($statsAfterClear['cache_size'])->toBe(0);
    });

    it('returns empty array for empty word', function () {
        $this->mockDictionary->shouldReceive('getWords')->andReturn(['สวัสดี', 'ครับ']);

        $suggestions = $this->strategy->suggest('', $this->mockDictionary);

        expect($suggestions)->toBeEmpty();
    });

    it('throws exception for invalid encoding', function () {
        expect(fn () => $this->strategy->suggest("\xFF\xFE", $this->mockDictionary))
            ->toThrow(SegmentationException::class);
    });

    it('can suggest words above threshold', function () {
        $this->mockDictionary->shouldReceive('getWords')->andReturn([
            'สวัสดี',
            'สวัสด',
            'ขอบคุณ',
            'สำเร็จ',
        ]);

        $this->strategy->setThreshold(0.7);

        $suggestions = $this->strategy->suggest('สวัสดี', $this->mockDictionary);

        expect($suggestions)->not->toBeEmpty()
            ->toBeArray();

        foreach ($suggestions as $suggestion) {
            expect($suggestion)->toHaveKeys(['word', 'score'])
                ->and($suggestion['word'])->toBeString()
                ->and($suggestion['score'])->toBeGreaterThanOrEqual(0.7);
        }
    });

    it('limits suggestions to max count', function () {
        $this->mockDictionary->shouldReceive('getWords')->andReturn([
            'สวัสดี',
            'สวัสด',
            'สวัส',
            'สวั',
            'ส',
        ]);

        $this->strategy->setThreshold(0.1); // Low threshold to get more results

        $suggestions = $this->strategy->suggest('สวัสดี', $this->mockDictionary, 2);

        expect(count($suggestions))->toBeLessThanOrEqual(2);
    });

    it('sorts suggestions by score descending', function () {
        $this->mockDictionary->shouldReceive('getWords')->andReturn([
            'สวัสด',    // Lower score
            'สวัสดี',   // Exact match (highest score)
            'สวัสดุ',   // Medium score
        ]);

        $this->strategy->setThreshold(0.1);

        $suggestions = $this->strategy->suggest('สวัสดี', $this->mockDictionary);

        expect($suggestions)->not->toBeEmpty();

        // Check that scores are in descending order
        for ($i = 0; $i < count($suggestions) - 1; $i++) {
            expect($suggestions[$i]['score'])->toBeGreaterThanOrEqual($suggestions[$i + 1]['score']);
        }
    });

    it('filters by word length difference', function () {
        $this->mockDictionary->shouldReceive('getWords')->andReturn([
            'สวัสดี',      // Same length
            'สวัสดีครับ',  // Much longer
            'สวัส',        // Much shorter
            'สวัสดุ',      // Same length
        ]);

        $this->strategy->setMaxWordLengthDiff(1);
        $this->strategy->setThreshold(0.1);

        $suggestions = $this->strategy->suggest('สวัสดี', $this->mockDictionary);

        // Should only include words with similar length
        foreach ($suggestions as $suggestion) {
            $lengthDiff = abs(
                mb_strlen('สวัสดี', 'UTF-8') - mb_strlen($suggestion['word'], 'UTF-8')
            );
            expect($lengthDiff)->toBeLessThanOrEqual(1);
        }
    });

    it('caches similarity calculations', function () {
        // First calculation should add to cache
        $similarity1 = $this->strategy->calculateSimilarity('สวัสดี', 'สวัสด');
        $statsAfterFirst = $this->strategy->getCacheStats();

        // Second calculation should use cache
        $similarity2 = $this->strategy->calculateSimilarity('สวัสดี', 'สวัสด');
        $statsAfterSecond = $this->strategy->getCacheStats();

        expect($similarity1)->toBe($similarity2)
            ->and($statsAfterFirst['cache_size'])->toBe($statsAfterSecond['cache_size']);
    });

    it('handles unicode characters correctly', function () {
        $similarity = $this->strategy->calculateSimilarity('สวัสดี', 'สวัสดี');

        expect($similarity)->toBe(1.0);
    });

    it('handles mixed thai and punctuation', function () {
        $this->mockDictionary->shouldReceive('getWords')->andReturn([
            'สวัสดี!',
            'สวัสดี?',
            'สวัสดี.',
        ]);

        $this->strategy->setThreshold(0.1);

        $suggestions = $this->strategy->suggest('สวัสดี', $this->mockDictionary);

        expect($suggestions)->not->toBeEmpty();
    });
});
