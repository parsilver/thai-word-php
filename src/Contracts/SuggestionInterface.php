<?php

declare(strict_types=1);

namespace Farzai\ThaiWord\Contracts;

interface SuggestionInterface
{
    /**
     * Find suggested words for incorrect input
     *
     * @param  string  $word  The incorrect word to find suggestions for
     * @param  DictionaryInterface  $dictionary  The dictionary to search in
     * @param  int  $maxSuggestions  Maximum number of suggestions to return
     * @return array<int, array{word: string, score: float}> Array of suggestions with scores
     *
     * @throws \Farzai\ThaiWord\Exceptions\SegmentationException
     */
    public function suggest(string $word, DictionaryInterface $dictionary, int $maxSuggestions = 5): array;

    /**
     * Calculate similarity score between two words
     *
     * @param  string  $word1  First word
     * @param  string  $word2  Second word
     * @return float Similarity score between 0.0 and 1.0
     */
    public function calculateSimilarity(string $word1, string $word2): float;

    /**
     * Set minimum similarity threshold for suggestions
     *
     * @param  float  $threshold  Minimum similarity score (0.0 to 1.0)
     */
    public function setThreshold(float $threshold): self;

    /**
     * Get current similarity threshold
     *
     * @return float Current threshold value
     */
    public function getThreshold(): float;
}
