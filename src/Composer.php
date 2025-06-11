<?php

declare(strict_types=1);

namespace Farzai\ThaiWord;

use Farzai\ThaiWord\Contracts\AlgorithmInterface;
use Farzai\ThaiWord\Contracts\DictionaryInterface;
use Farzai\ThaiWord\Contracts\SuggestionInterface;
use Farzai\ThaiWord\Segmenter\ThaiSegmenter;

/**
 * Laravel-inspired facade for Thai word segmentation
 *
 * This facade provides a convenient static interface to the ThaiSegmenter
 * functionality, similar to Laravel's facade pattern.
 *
 * Usage examples:
 * - Composer::segment('สวัสดีครับ')
 * - Composer::segmentToString('สวัสดีครับ', ' ')
 * - Composer::segmentBatch(['สวัสดี', 'ขอบคุณ'])
 * - Composer::suggest('สวัสด')
 * - Composer::segmentWithSuggestions('สวัสดีครบ')
 * - Composer::enableSuggestions(['threshold' => 0.7])
 * - Composer::getStats()
 */
class Composer
{
    /**
     * The underlying segmenter instance
     */
    private static ?ThaiSegmenter $segmenter = null;

    /**
     * Segment Thai text into an array of words
     *
     * @param  string  $text  The Thai text to segment
     * @return array<string> Array of segmented words
     */
    public static function segment(string $text): array
    {
        return self::getSegmenter()->segment($text);
    }

    /**
     * Segment Thai text and return as delimited string
     *
     * @param  string  $text  The Thai text to segment
     * @param  string  $delimiter  The delimiter to use between words (default: '|')
     * @return string Segmented text with delimiters
     */
    public static function segmentToString(string $text, string $delimiter = '|'): string
    {
        return self::getSegmenter()->segmentToString($text, $delimiter);
    }

    /**
     * Segment multiple texts in batch for better performance
     *
     * @param  array<string>  $texts  Array of texts to segment
     * @return array<int, array<string>> Array of segmentation results
     */
    public static function segmentBatch(array $texts): array
    {
        return self::getSegmenter()->segmentBatch($texts);
    }

    /**
     * Get performance statistics
     *
     * @return array<string, mixed> Performance statistics
     */
    public static function getStats(): array
    {
        return self::getSegmenter()->getStats();
    }

    /**
     * Reset performance statistics
     */
    public static function resetStats(): void
    {
        self::getSegmenter()->resetStats();
    }

    /**
     * Optimize memory usage
     */
    public static function optimizeMemory(): void
    {
        self::getSegmenter()->optimizeMemory();
    }

    /**
     * Update segmenter configuration
     *
     * @param  array<string, mixed>  $config  Configuration options
     */
    public static function updateConfig(array $config): void
    {
        self::getSegmenter()->updateConfig($config);
    }

    /**
     * Get current configuration
     *
     * @return array<string, mixed> Current configuration
     */
    public static function getConfig(): array
    {
        return self::getSegmenter()->getConfig();
    }

    /**
     * Get the dictionary instance
     */
    public static function getDictionary(): DictionaryInterface
    {
        return self::getSegmenter()->getDictionary();
    }

    /**
     * Get the algorithm instance
     */
    public static function getAlgorithm(): AlgorithmInterface
    {
        return self::getSegmenter()->getAlgorithm();
    }

    /**
     * Find suggestions for potentially incorrect words
     *
     * @param  string  $word  The word to find suggestions for
     * @param  int|null  $maxSuggestions  Maximum number of suggestions to return
     * @return array<int, array{word: string, score: float}> Array of suggestions with scores
     */
    public static function suggest(string $word, ?int $maxSuggestions = null): array
    {
        return self::getSegmenter()->suggest($word, $maxSuggestions);
    }

    /**
     * Segment text with suggestions for unrecognized words
     *
     * @param  string  $text  The text to segment
     * @return array<int, array{word: string, suggestions?: array}> Segmented words with optional suggestions
     */
    public static function segmentWithSuggestions(string $text): array
    {
        return self::getSegmenter()->segmentWithSuggestions($text);
    }

    /**
     * Enable suggestions with default or custom configuration
     *
     * @param  array  $config  Suggestion configuration options
     */
    public static function enableSuggestions(array $config = []): void
    {
        self::getSegmenter()->enableSuggestions($config);
    }

    /**
     * Disable suggestions
     */
    public static function disableSuggestions(): void
    {
        self::getSegmenter()->disableSuggestions();
    }

    /**
     * Get suggestion strategy instance
     */
    public static function getSuggestionStrategy(): ?SuggestionInterface
    {
        return self::getSegmenter()->getSuggestionStrategy();
    }

    /**
     * Set suggestion strategy
     */
    public static function setSuggestionStrategy(?SuggestionInterface $suggestionStrategy): void
    {
        self::getSegmenter()->setSuggestionStrategy($suggestionStrategy);
    }

    /**
     * Create a new segmenter instance with custom configuration
     *
     * @param  DictionaryInterface|null  $dictionary  Custom dictionary instance
     * @param  AlgorithmInterface|null  $algorithm  Custom algorithm instance
     * @param  SuggestionInterface|null  $suggestionStrategy  Custom suggestion strategy instance
     * @param  array<string, mixed>  $config  Configuration options
     * @return ThaiSegmenter New segmenter instance
     */
    public static function create(
        ?DictionaryInterface $dictionary = null,
        ?AlgorithmInterface $algorithm = null,
        ?SuggestionInterface $suggestionStrategy = null,
        array $config = []
    ): ThaiSegmenter {
        return new ThaiSegmenter($dictionary, $algorithm, $suggestionStrategy, $config);
    }

    /**
     * Set a custom segmenter instance
     *
     * This allows you to configure the segmenter with specific
     * dictionary and algorithm instances.
     */
    public static function setSegmenter(ThaiSegmenter $segmenter): void
    {
        self::$segmenter = $segmenter;
    }

    /**
     * Clear the current segmenter instance
     *
     * This will force the creation of a new default instance
     * on the next method call.
     */
    public static function clearInstance(): void
    {
        self::$segmenter = null;
    }

    /**
     * Get or create segmenter instance
     */
    private static function getSegmenter(): ThaiSegmenter
    {
        if (self::$segmenter === null) {
            self::$segmenter = new ThaiSegmenter;
        }

        return self::$segmenter;
    }
}
