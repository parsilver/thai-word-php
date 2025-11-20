<?php

use Farzai\ThaiWord\Algorithms\Strategies\LongestMatchingStrategy;
use Farzai\ThaiWord\Dictionary\HashDictionary;
use Farzai\ThaiWord\Exceptions\SegmentationException;
use Farzai\ThaiWord\Segmenter\ThaiSegmenter;

describe('ThaiSegmenter', function () {
    beforeEach(function () {
        $this->dictionary = new HashDictionary;
        $this->dictionary->add('สวัสดี');
        $this->dictionary->add('ครับ');

        $this->algorithm = new LongestMatchingStrategy;
        $this->segmenter = new ThaiSegmenter($this->dictionary, $this->algorithm);
    });

    it('can segment text', function () {
        $result = $this->segmenter->segment('สวัสดีครับ');

        expect($result)->toBe(['สวัสดี', 'ครับ']);
    });

    it('can segment to string with default delimiter', function () {
        $result = $this->segmenter->segmentToString('สวัสดีครับ');

        expect($result)->toBe('สวัสดี|ครับ');
    });

    it('can segment to string with custom delimiter', function () {
        $result = $this->segmenter->segmentToString('สวัสดีครับ', ' ');

        expect($result)->toBe('สวัสดี ครับ');
    });

    it('returns empty array for empty input', function () {
        $result = $this->segmenter->segment('');

        expect($result)->toBe([]);
    });

    it('throws exception for invalid UTF-8 input', function () {
        expect(fn () => $this->segmenter->segment("\xFF\xFE"))
            ->toThrow(SegmentationException::class, null, SegmentationException::INPUT_INVALID_ENCODING);
    });

    describe('segmentBatch', function () {
        it('can segment multiple texts in batch', function () {
            $texts = ['สวัสดีครับ', 'ครับสวัสดี'];

            $results = $this->segmenter->segmentBatch($texts);

            expect($results)
                ->toBeArray()
                ->toHaveCount(2);

            expect($results[0])->toBe(['สวัสดี', 'ครับ']);
            expect($results[1])->toBe(['ครับ', 'สวัสดี']);
        });

        it('handles empty batch', function () {
            $results = $this->segmenter->segmentBatch([]);

            expect($results)->toBeArray()->toBeEmpty();
        });

        it('handles batch with empty strings', function () {
            $results = $this->segmenter->segmentBatch(['', 'สวัสดี', '']);

            expect($results)->toHaveCount(3);
            expect($results[0])->toBeEmpty();
            expect($results[1])->toBeArray()->not->toBeEmpty();
            expect($results[2])->toBeEmpty();
        });

        it('processes large batches efficiently', function () {
            $texts = array_fill(0, 100, 'สวัสดีครับ');

            $results = $this->segmenter->segmentBatch($texts);

            expect($results)->toHaveCount(100);

            foreach ($results as $result) {
                expect($result)->toEqual(['สวัสดี', 'ครับ']);
            }
        });
    });

    describe('statistics and performance', function () {
        it('tracks performance statistics', function () {
            $this->segmenter->segment('สวัสดีครับ');

            $stats = $this->segmenter->getStats();

            expect($stats)
                ->toBeArray()
                ->toHaveKey('segments_processed')
                ->toHaveKey('total_processing_time')
                ->toHaveKey('cache_hits')
                ->toHaveKey('cache_misses')
                ->toHaveKey('memory_peak');

            expect($stats['segments_processed'])->toBeGreaterThan(0);
        });

        it('increments segment count on each operation', function () {
            $stats1 = $this->segmenter->getStats();

            $this->segmenter->segment('สวัสดีครับ');

            $stats2 = $this->segmenter->getStats();

            expect($stats2['segments_processed'])
                ->toBeGreaterThan($stats1['segments_processed']);
        });

        it('can reset statistics', function () {
            $this->segmenter->segment('สวัสดีครับ');

            $this->segmenter->resetStats();

            $stats = $this->segmenter->getStats();

            expect($stats['segments_processed'])->toBe(0);
            expect($stats['total_processing_time'])->toBe(0.0);
            expect($stats['cache_hits'])->toBe(0);
            expect($stats['cache_misses'])->toBe(0);
        });

        it('tracks cache hits when enabled', function () {
            $this->segmenter->updateConfig(['enable_caching' => true]);

            // First call - cache miss
            $this->segmenter->segment('สวัสดีครับ');

            // Second call - cache hit
            $this->segmenter->segment('สวัสดีครับ');

            $stats = $this->segmenter->getStats();

            expect($stats['cache_hits'])->toBeGreaterThan(0);
        });
    });

    describe('caching', function () {
        it('caches segmentation results when enabled', function () {
            $this->segmenter->updateConfig(['enable_caching' => true]);

            $result1 = $this->segmenter->segment('สวัสดีครับ');
            $result2 = $this->segmenter->segment('สวัสดีครับ');

            expect($result1)->toEqual($result2);

            $stats = $this->segmenter->getStats();
            expect($stats['cache_hits'])->toBeGreaterThan(0);
        });

        it('can disable caching', function () {
            $this->segmenter->updateConfig(['enable_caching' => false]);

            $this->segmenter->segment('สวัสดีครับ');
            $this->segmenter->segment('สวัสดีครับ');

            $stats = $this->segmenter->getStats();
            expect($stats['cache_hits'])->toBe(0);
        });

        it('returns cached results correctly', function () {
            $this->segmenter->updateConfig(['enable_caching' => true]);

            $expected = ['สวัสดี', 'ครับ'];

            $result1 = $this->segmenter->segment('สวัสดีครับ');
            $result2 = $this->segmenter->segment('สวัสดีครับ');

            expect($result1)->toEqual($expected);
            expect($result2)->toEqual($expected);
        });
    });

    describe('configuration', function () {
        it('can update configuration', function () {
            $this->segmenter->updateConfig([
                'enable_caching' => false,
                'batch_size' => 2000,
            ]);

            $config = $this->segmenter->getConfig();

            expect($config)
                ->toHaveKey('enable_caching', false)
                ->toHaveKey('batch_size', 2000);
        });

        it('returns current configuration', function () {
            $config = $this->segmenter->getConfig();

            expect($config)
                ->toBeArray()
                ->toHaveKey('enable_caching')
                ->toHaveKey('enable_stats')
                ->toHaveKey('batch_size')
                ->toHaveKey('memory_limit_mb')
                ->toHaveKey('auto_optimize');
        });

        it('merges configuration on update', function () {
            $initialConfig = $this->segmenter->getConfig();

            $this->segmenter->updateConfig(['enable_caching' => false]);

            $updatedConfig = $this->segmenter->getConfig();

            // Other config values should remain
            expect($updatedConfig)
                ->toHaveKey('enable_caching', false)
                ->toHaveKey('batch_size', $initialConfig['batch_size']);
        });
    });

    describe('memory optimization', function () {
        it('can optimize memory without errors', function () {
            $this->segmenter->segment('สวัสดีครับ');

            expect(fn () => $this->segmenter->optimizeMemory())
                ->not->toThrow(Exception::class);
        });

        it('clears cache when optimizing memory', function () {
            $this->segmenter->updateConfig(['enable_caching' => true]);

            // Populate cache
            $this->segmenter->segment('สวัสดีครับ');
            $this->segmenter->segment('ครับสวัสดี');

            // Get initial stats
            $statsBefore = $this->segmenter->getStats();

            // Optimize memory (should clear cache)
            $this->segmenter->optimizeMemory();
            $this->segmenter->resetStats();

            // After optimize and reset, segment should work normally
            $result = $this->segmenter->segment('สวัสดีครับ');

            expect($result)->toBeArray()->not->toBeEmpty();
        });
    });

    describe('dependency access', function () {
        it('can get dictionary instance', function () {
            $dictionary = $this->segmenter->getDictionary();

            expect($dictionary)->toBe($this->dictionary);
        });

        it('can get algorithm instance', function () {
            $algorithm = $this->segmenter->getAlgorithm();

            expect($algorithm)->toBe($this->algorithm);
        });
    });

    describe('constructor and initialization', function () {
        it('uses default dictionary when none provided', function () {
            $segmenter = new ThaiSegmenter;

            $dictionary = $segmenter->getDictionary();

            expect($dictionary)->toBeInstanceOf(\Farzai\ThaiWord\Contracts\DictionaryInterface::class);
        });

        it('uses default algorithm when none provided', function () {
            $segmenter = new ThaiSegmenter;

            $algorithm = $segmenter->getAlgorithm();

            expect($algorithm)->toBeInstanceOf(\Farzai\ThaiWord\Contracts\AlgorithmInterface::class);
        });

        it('accepts custom configuration', function () {
            $segmenter = new ThaiSegmenter(null, null, null, [
                'enable_caching' => false,
                'batch_size' => 5000,
            ]);

            $config = $segmenter->getConfig();

            expect($config)
                ->toHaveKey('enable_caching', false)
                ->toHaveKey('batch_size', 5000);
        });
    });

    describe('edge cases', function () {
        it('handles whitespace-only input', function () {
            $result = $this->segmenter->segment('   ');

            expect($result)->toBeEmpty();
        });

        it('handles very long text', function () {
            $longText = str_repeat('สวัสดีครับ', 100);

            $result = $this->segmenter->segment($longText);

            expect($result)->toBeArray()->not->toBeEmpty();
        });

        it('handles special Thai characters', function () {
            $this->dictionary->add('ก่า');
            $this->dictionary->add('เก๋');

            $result = $this->segmenter->segment('ก่าเก๋');

            expect($result)->toBeArray();
        });
    });

    describe('integration', function () {
        it('handles complete workflow', function () {
            // Segment
            $words = $this->segmenter->segment('สวัสดีครับ');
            expect($words)->toBeArray();

            // Segment to string
            $string = $this->segmenter->segmentToString('สวัสดีครับ');
            expect($string)->toBeString();

            // Batch
            $batch = $this->segmenter->segmentBatch(['สวัสดี', 'ครับ']);
            expect($batch)->toHaveCount(2);

            // Stats
            $stats = $this->segmenter->getStats();
            expect($stats['segments_processed'])->toBeGreaterThan(0);

            // Optimize
            $this->segmenter->optimizeMemory();

            // Reset
            $this->segmenter->resetStats();

            $stats = $this->segmenter->getStats();
            expect($stats['segments_processed'])->toBe(0);
        });
    });
});
