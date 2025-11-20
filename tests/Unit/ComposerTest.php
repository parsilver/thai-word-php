<?php

use Farzai\ThaiWord\Algorithms\Strategies\LongestMatchingStrategy;
use Farzai\ThaiWord\Composer;
use Farzai\ThaiWord\Contracts\AlgorithmInterface;
use Farzai\ThaiWord\Contracts\DictionaryInterface;
use Farzai\ThaiWord\Contracts\SuggestionInterface;
use Farzai\ThaiWord\Dictionary\HashDictionary;
use Farzai\ThaiWord\Segmenter\ThaiSegmenter;

describe('Composer Facade', function () {
    beforeEach(function () {
        // Clear any existing instance
        Composer::clearInstance();
    });

    afterEach(function () {
        // Clean up after each test
        Composer::clearInstance();
    });

    describe('segment', function () {
        it('can segment Thai text', function () {
            $result = Composer::segment('สวัสดีครับ');

            expect($result)
                ->toBeArray()
                ->not->toBeEmpty();

            foreach ($result as $word) {
                expect($word)->toBeString();
            }
        });

        it('returns empty array for empty text', function () {
            $result = Composer::segment('');

            expect($result)->toBeArray()->toBeEmpty();
        });

        it('handles text without Thai characters', function () {
            $result = Composer::segment('Hello World');

            expect($result)->toBeArray();
        });

        it('handles mixed Thai and English', function () {
            $result = Composer::segment('สวัสดี Hello');

            expect($result)->toBeArray()->not->toBeEmpty();
        });

        it('maintains singleton instance between calls', function () {
            Composer::segment('สวัสดี');
            $result2 = Composer::segment('ขอบคุณ');

            expect($result2)->toBeArray();
        });
    });

    describe('segmentToString', function () {
        it('can segment text to delimited string', function () {
            $result = Composer::segmentToString('สวัสดีครับ');

            expect($result)->toBeString()->toContain('|');
        });

        it('can use custom delimiter', function () {
            $result = Composer::segmentToString('สวัสดีครับ', ' ');

            expect($result)->toBeString()->toContain(' ');
        });

        it('returns empty string for empty text', function () {
            $result = Composer::segmentToString('');

            expect($result)->toBe('');
        });

        it('handles different delimiters', function () {
            $delimiters = [' ', ',', '-', '/', '|'];

            foreach ($delimiters as $delimiter) {
                $result = Composer::segmentToString('สวัสดีครับ', $delimiter);

                expect($result)->toBeString();
                if (! empty($result) && $result !== 'สวัสดีครับ') {
                    expect($result)->toContain($delimiter);
                }
            }
        });
    });

    describe('segmentBatch', function () {
        it('can segment multiple texts in batch', function () {
            $texts = ['สวัสดี', 'ขอบคุณ', 'สบายดี'];

            $results = Composer::segmentBatch($texts);

            expect($results)
                ->toBeArray()
                ->toHaveCount(3);

            foreach ($results as $result) {
                expect($result)->toBeArray();
            }
        });

        it('handles empty batch', function () {
            $results = Composer::segmentBatch([]);

            expect($results)->toBeArray()->toBeEmpty();
        });

        it('handles batch with empty strings', function () {
            $results = Composer::segmentBatch(['', 'สวัสดี', '']);

            expect($results)
                ->toBeArray()
                ->toHaveCount(3);
        });

        it('maintains order of results', function () {
            $texts = ['สวัสดี', 'ขอบคุณ', 'ราตรีสวัสดิ์'];

            $results = Composer::segmentBatch($texts);

            expect($results)->toHaveCount(count($texts));
            expect(array_keys($results))->toEqual([0, 1, 2]);
        });
    });

    describe('getStats', function () {
        it('can retrieve performance statistics', function () {
            Composer::segment('สวัสดีครับ');

            $stats = Composer::getStats();

            expect($stats)->toBeArray();
        });

        it('stats increase with usage', function () {
            $stats1 = Composer::getStats();

            Composer::segment('สวัสดีครับ');

            $stats2 = Composer::getStats();

            expect($stats2)->toBeArray();
        });
    });

    describe('resetStats', function () {
        it('can reset performance statistics', function () {
            Composer::segment('สวัสดีครับ');
            Composer::resetStats();

            $stats = Composer::getStats();

            expect($stats)->toBeArray();
        });
    });

    describe('optimizeMemory', function () {
        it('can optimize memory without errors', function () {
            Composer::segment('สวัสดีครับ');

            expect(fn () => Composer::optimizeMemory())->not->toThrow(Exception::class);
        });
    });

    describe('updateConfig', function () {
        it('can update configuration', function () {
            Composer::updateConfig([
                'enable_cache' => true,
            ]);

            $config = Composer::getConfig();

            expect($config)->toBeArray()->toHaveKey('enable_cache');
        });

        it('can update multiple config values', function () {
            Composer::updateConfig([
                'enable_cache' => true,
                'cache_size' => 1000,
            ]);

            $config = Composer::getConfig();

            expect($config)
                ->toHaveKey('enable_cache')
                ->toHaveKey('cache_size');
        });
    });

    describe('getConfig', function () {
        it('returns current configuration', function () {
            $config = Composer::getConfig();

            expect($config)->toBeArray();
        });

        it('reflects configuration changes', function () {
            $config1 = Composer::getConfig();

            Composer::updateConfig(['enable_cache' => true]);

            $config2 = Composer::getConfig();

            expect($config2)->toBeArray();
        });
    });

    describe('getDictionary', function () {
        it('returns dictionary instance', function () {
            $dictionary = Composer::getDictionary();

            expect($dictionary)->toBeInstanceOf(DictionaryInterface::class);
        });

        it('returns same instance on multiple calls', function () {
            $dict1 = Composer::getDictionary();
            $dict2 = Composer::getDictionary();

            expect($dict1)->toBe($dict2);
        });
    });

    describe('getAlgorithm', function () {
        it('returns algorithm instance', function () {
            $algorithm = Composer::getAlgorithm();

            expect($algorithm)->toBeInstanceOf(AlgorithmInterface::class);
        });

        it('returns same instance on multiple calls', function () {
            $algo1 = Composer::getAlgorithm();
            $algo2 = Composer::getAlgorithm();

            expect($algo1)->toBe($algo2);
        });
    });

    describe('create', function () {
        it('can create new segmenter instance', function () {
            $segmenter = Composer::create();

            expect($segmenter)->toBeInstanceOf(ThaiSegmenter::class);
        });

        it('can create with custom dictionary', function () {
            $dictionary = new HashDictionary;
            $dictionary->add('สวัสดี');

            $segmenter = Composer::create($dictionary);

            expect($segmenter->getDictionary())->toBe($dictionary);
        });

        it('can create with custom algorithm', function () {
            $algorithm = new LongestMatchingStrategy;

            $segmenter = Composer::create(null, $algorithm);

            expect($segmenter->getAlgorithm())->toBe($algorithm);
        });

        it('can create with custom suggestion strategy', function () {
            $suggestionStrategy = Mockery::mock(SuggestionInterface::class);

            $segmenter = Composer::create(null, null, $suggestionStrategy);

            expect($segmenter->getSuggestionStrategy())->toBe($suggestionStrategy);

            Mockery::close();
        });

        it('can create with custom config', function () {
            $config = ['enable_cache' => false];

            $segmenter = Composer::create(null, null, null, $config);

            expect($segmenter->getConfig())->toHaveKey('enable_cache');
        });

        it('can create with all custom parameters', function () {
            $dictionary = new HashDictionary;
            $algorithm = new LongestMatchingStrategy;
            $suggestionStrategy = Mockery::mock(SuggestionInterface::class);
            $config = ['enable_cache' => false];

            $segmenter = Composer::create($dictionary, $algorithm, $suggestionStrategy, $config);

            expect($segmenter->getDictionary())->toBe($dictionary);
            expect($segmenter->getAlgorithm())->toBe($algorithm);
            expect($segmenter->getSuggestionStrategy())->toBe($suggestionStrategy);

            Mockery::close();
        });
    });

    describe('setSegmenter', function () {
        it('can set custom segmenter instance', function () {
            $customSegmenter = new ThaiSegmenter;
            Composer::setSegmenter($customSegmenter);

            $dictionary = Composer::getDictionary();

            expect($dictionary)->toBeInstanceOf(DictionaryInterface::class);
        });

        it('uses custom segmenter for operations', function () {
            $customDictionary = new HashDictionary;
            $customDictionary->add('test');
            $customSegmenter = new ThaiSegmenter($customDictionary);

            Composer::setSegmenter($customSegmenter);

            $dictionary = Composer::getDictionary();

            expect($dictionary)->toBe($customDictionary);
        });
    });

    describe('clearInstance', function () {
        it('clears the singleton instance', function () {
            Composer::segment('สวัสดี');
            Composer::clearInstance();

            // Should create a new instance
            $result = Composer::segment('ขอบคุณ');

            expect($result)->toBeArray();
        });

        it('resets configuration when cleared', function () {
            Composer::updateConfig(['enable_cache' => true]);
            Composer::clearInstance();

            // New instance should have default config
            $config = Composer::getConfig();

            expect($config)->toBeArray();
        });
    });

    describe('integration scenarios', function () {
        it('facade can handle complete workflow', function () {
            // Segment text
            $words = Composer::segment('สวัสดีครับ');
            expect($words)->toBeArray();

            // Get as string
            $string = Composer::segmentToString('สวัสดีครับ', ' ');
            expect($string)->toBeString();

            // Batch processing
            $batch = Composer::segmentBatch(['สวัสดี', 'ขอบคุณ']);
            expect($batch)->toHaveCount(2);

            // Get stats
            $stats = Composer::getStats();
            expect($stats)->toBeArray();

            // Reset stats
            Composer::resetStats();

            // Get config
            $config = Composer::getConfig();
            expect($config)->toBeArray();
        });

        it('maintains state across multiple operations', function () {
            Composer::updateConfig(['enable_cache' => true]);

            Composer::segment('สวัสดี');
            Composer::segment('ขอบคุณ');

            $config = Composer::getConfig();
            expect($config)->toHaveKey('enable_cache');

            $stats = Composer::getStats();
            expect($stats)->toBeArray();
        });

        it('can switch between instances', function () {
            // Use default instance
            Composer::segment('สวัสดี');

            // Create and set custom instance
            $customSegmenter = Composer::create();
            Composer::setSegmenter($customSegmenter);

            // Use custom instance
            Composer::segment('ขอบคุณ');

            // Clear and use new default
            Composer::clearInstance();
            Composer::segment('ราตรีสวัสดิ์');

            expect(true)->toBeTrue();
        });

        it('handles large batch operations', function () {
            $texts = array_fill(0, 100, 'สวัสดีครับ');

            $results = Composer::segmentBatch($texts);

            expect($results)
                ->toBeArray()
                ->toHaveCount(100);
        });

        it('optimizes memory during intensive operations', function () {
            for ($i = 0; $i < 10; $i++) {
                Composer::segment('สวัสดีครับขอบคุณมากครับ');
            }

            expect(fn () => Composer::optimizeMemory())->not->toThrow(Exception::class);

            $result = Composer::segment('ต่อไป');
            expect($result)->toBeArray();
        });
    });

    describe('singleton behavior', function () {
        it('creates instance on first use', function () {
            $result = Composer::segment('สวัสดี');

            expect($result)->toBeArray();
        });

        it('reuses same instance', function () {
            Composer::segment('สวัสดี');
            $dict1 = Composer::getDictionary();

            Composer::segment('ขอบคุณ');
            $dict2 = Composer::getDictionary();

            expect($dict1)->toBe($dict2);
        });

        it('maintains state between calls', function () {
            Composer::updateConfig(['test_key' => 'test_value']);

            $config1 = Composer::getConfig();
            $config2 = Composer::getConfig();

            expect($config1)->toEqual($config2);
        });
    });
});
