<?php

declare(strict_types=1);

namespace Farzai\ThaiWord\Suggestions\Strategies;

use Farzai\ThaiWord\Contracts\DictionaryInterface;
use Farzai\ThaiWord\Contracts\SuggestionInterface;
use Farzai\ThaiWord\Exceptions\SegmentationException;

/**
 * Levenshtein distance-based word suggestion strategy
 *
 * This strategy uses the Levenshtein distance algorithm to find similar words
 * in the dictionary. It's optimized for Thai text with Unicode support and
 * performance enhancements for large dictionaries.
 *
 * Features:
 * - Unicode-aware string comparison
 * - Configurable similarity threshold
 * - Length-based filtering for performance
 * - Cached distance calculations
 */
class LevenshteinSuggestionStrategy implements SuggestionInterface
{
    private float $threshold = 0.6;

    private int $maxWordLengthDiff = 3;

    /**
     * Distance calculation cache
     *
     * @var array<string, float>
     */
    private array $distanceCache = [];

    private const MAX_CACHE_SIZE = 1000;

    public function suggest(string $word, DictionaryInterface $dictionary, int $maxSuggestions = 5): array
    {
        if (trim($word) === '') {
            return [];
        }

        if (! mb_check_encoding($word, 'UTF-8')) {
            throw new SegmentationException(
                'Input word must be valid UTF-8 encoded',
                SegmentationException::INPUT_INVALID_ENCODING
            );
        }

        $word = trim($word);
        $wordLength = mb_strlen($word, 'UTF-8');
        $suggestions = [];

        // Get dictionary words with length filtering for performance
        $dictionaryWords = $this->getFilteredDictionaryWords($dictionary, $wordLength);

        foreach ($dictionaryWords as $dictWord) {
            $similarity = $this->calculateSimilarity($word, $dictWord);

            if ($similarity >= $this->threshold) {
                $suggestions[] = [
                    'word' => $dictWord,
                    'score' => $similarity,
                ];
            }
        }

        // Sort by similarity score (descending)
        usort($suggestions, fn ($a, $b) => $b['score'] <=> $a['score']);

        // Return limited results
        return array_slice($suggestions, 0, $maxSuggestions);
    }

    public function calculateSimilarity(string $word1, string $word2): float
    {
        if ($word1 === $word2) {
            return 1.0;
        }

        $cacheKey = $word1.'|'.$word2;
        if (isset($this->distanceCache[$cacheKey])) {
            return $this->distanceCache[$cacheKey];
        }

        // Handle cache size limit
        if (count($this->distanceCache) >= self::MAX_CACHE_SIZE) {
            $this->distanceCache = array_slice($this->distanceCache, -self::MAX_CACHE_SIZE / 2, null, true);
        }

        $distance = $this->calculateLevenshteinDistance($word1, $word2);
        $maxLength = max(mb_strlen($word1, 'UTF-8'), mb_strlen($word2, 'UTF-8'));

        // Convert distance to similarity score (0.0 to 1.0)
        $similarity = $maxLength > 0 ? 1.0 - ($distance / $maxLength) : 1.0;

        // Cache the result
        $this->distanceCache[$cacheKey] = $similarity;

        return $similarity;
    }

    public function setThreshold(float $threshold): self
    {
        if ($threshold < 0.0 || $threshold > 1.0) {
            throw new SegmentationException(
                'Threshold must be between 0.0 and 1.0',
                SegmentationException::ALGORITHM_PROCESSING_FAILED
            );
        }

        $this->threshold = $threshold;

        return $this;
    }

    public function getThreshold(): float
    {
        return $this->threshold;
    }

    /**
     * Set maximum word length difference for filtering
     *
     * @param  int  $maxDiff  Maximum length difference between words
     */
    public function setMaxWordLengthDiff(int $maxDiff): self
    {
        $this->maxWordLengthDiff = max(1, $maxDiff);

        return $this;
    }

    /**
     * Get maximum word length difference
     *
     * @return int Current maximum length difference
     */
    public function getMaxWordLengthDiff(): int
    {
        return $this->maxWordLengthDiff;
    }

    /**
     * Clear the distance calculation cache
     */
    public function clearCache(): self
    {
        $this->distanceCache = [];

        return $this;
    }

    /**
     * Get cache statistics
     *
     * @return array<string, mixed> Cache statistics
     */
    public function getCacheStats(): array
    {
        return [
            'cache_size' => count($this->distanceCache),
            'max_cache_size' => self::MAX_CACHE_SIZE,
            'memory_usage_mb' => $this->estimateCacheMemoryUsage(),
        ];
    }

    /**
     * Calculate Unicode-aware Levenshtein distance
     *
     * @param  string  $str1  First string
     * @param  string  $str2  Second string
     * @return int Levenshtein distance
     */
    private function calculateLevenshteinDistance(string $str1, string $str2): int
    {
        // Convert strings to arrays of Unicode characters
        $chars1 = $this->mbStrSplit($str1);
        $chars2 = $this->mbStrSplit($str2);

        $len1 = count($chars1);
        $len2 = count($chars2);

        // Handle edge cases
        if ($len1 === 0) {
            return $len2;
        }
        if ($len2 === 0) {
            return $len1;
        }

        // Initialize matrix
        $matrix = [];
        for ($i = 0; $i <= $len1; $i++) {
            $matrix[$i][0] = $i;
        }
        for ($j = 0; $j <= $len2; $j++) {
            $matrix[0][$j] = $j;
        }

        // Fill matrix
        for ($i = 1; $i <= $len1; $i++) {
            for ($j = 1; $j <= $len2; $j++) {
                $cost = ($chars1[$i - 1] === $chars2[$j - 1]) ? 0 : 1;

                $matrix[$i][$j] = min(
                    $matrix[$i - 1][$j] + 1,     // deletion
                    $matrix[$i][$j - 1] + 1,     // insertion
                    $matrix[$i - 1][$j - 1] + $cost // substitution
                );
            }
        }

        return $matrix[$len1][$len2];
    }

    /**
     * Split multibyte string into array of characters
     *
     * @param  string  $str  Input string
     * @return array<int, string> Array of characters
     */
    private function mbStrSplit(string $str): array
    {
        $chars = [];
        $length = mb_strlen($str, 'UTF-8');

        for ($i = 0; $i < $length; $i++) {
            $chars[] = mb_substr($str, $i, 1, 'UTF-8');
        }

        return $chars;
    }

    /**
     * Get dictionary words filtered by length for performance
     *
     * @param  DictionaryInterface  $dictionary  Dictionary to search
     * @param  int  $targetLength  Target word length
     * @return array<int, string> Filtered dictionary words
     */
    private function getFilteredDictionaryWords(DictionaryInterface $dictionary, int $targetLength): array
    {
        $words = $dictionary->getWords();
        $filtered = [];

        foreach ($words as $word) {
            $wordLength = mb_strlen($word, 'UTF-8');

            // Filter by length difference to improve performance
            if (abs($wordLength - $targetLength) <= $this->maxWordLengthDiff) {
                $filtered[] = $word;
            }
        }

        return $filtered;
    }

    /**
     * Estimate cache memory usage in MB
     *
     * @return float Estimated memory usage
     */
    private function estimateCacheMemoryUsage(): float
    {
        $avgKeySize = 20; // Average cache key size in bytes
        $avgValueSize = 8; // Float size in bytes
        $overhead = 50; // PHP array overhead per entry

        $totalSize = count($this->distanceCache) * ($avgKeySize + $avgValueSize + $overhead);

        return $totalSize / 1024 / 1024;
    }
}
