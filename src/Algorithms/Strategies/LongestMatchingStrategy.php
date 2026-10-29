<?php

declare(strict_types=1);

namespace Farzai\ThaiWord\Algorithms\Strategies;

use Farzai\ThaiWord\Contracts\AlgorithmInterface;
use Farzai\ThaiWord\Contracts\DictionaryInterface;
use Farzai\ThaiWord\Exceptions\AlgorithmException;
use Farzai\ThaiWord\Exceptions\SegmentationException;

/**
 * Longest Matching Strategy for Thai word segmentation
 *
 * At each position the strategy takes the longest dictionary word that does
 * not split a Thai character cluster. When no word matches, it emits one
 * character cluster (a consonant together with its vowels and tone marks)
 * instead of a lone code point, so marks such as "่" or "์" never become
 * tokens of their own.
 *
 * Latin words, Arabic and Thai digit runs, whitespace and punctuation are
 * handled without dictionary lookups. Runs of whitespace (including line
 * breaks) are normalized to a single " " token.
 *
 * The text is split into characters once, so the cost per position does not
 * grow with the length of the input.
 */
class LongestMatchingStrategy implements AlgorithmInterface
{
    /**
     * Maximum word length to consider during matching
     * Will be dynamically adjusted based on dictionary
     */
    private int $maxWordLength = 20;

    /**
     * Cache of match lengths for the current call, keyed by the look-ahead
     * window that decides them
     *
     * @var array<string, int>
     */
    private array $matchCache = [];

    /**
     * Maximum cache size to prevent memory bloat
     */
    private const MAX_CACHE_SIZE = 1000;

    private const THAI_RANGE_START = 0x0E00;

    private const THAI_RANGE_END = 0x0E7F;

    private const THAI_CONSONANT_START = 0x0E01;

    private const THAI_CONSONANT_END = 0x0E2E;

    private const THAI_DIGIT_START = 0x0E50;

    private const THAI_DIGIT_END = 0x0E59;

    /**
     * Leading vowels (เ แ โ ใ ไ) are written before the consonant they belong to
     */
    private const THAI_LEADING_VOWEL_START = 0x0E40;

    private const THAI_LEADING_VOWEL_END = 0x0E44;

    /**
     * Characters that can never start a Thai syllable: they belong to the
     * character before them (following vowels, above/below vowels, tone marks
     * and other diacritics)
     *
     * @var array<int, true>
     */
    private const THAI_ATTACHING = [
        0x0E30 => true, // sara a
        0x0E31 => true, // mai han-akat
        0x0E32 => true, // sara aa
        0x0E33 => true, // sara am
        0x0E34 => true, 0x0E35 => true, 0x0E36 => true, 0x0E37 => true,
        0x0E38 => true, 0x0E39 => true, 0x0E3A => true,
        0x0E45 => true, // lakkhangyao
        0x0E47 => true, 0x0E48 => true, 0x0E49 => true, 0x0E4A => true,
        0x0E4B => true, 0x0E4C => true, 0x0E4D => true, 0x0E4E => true,
    ];

    public function process(string $text, DictionaryInterface $dictionary): array
    {
        // Handle empty or whitespace-only input
        if (trim($text) === '') {
            return [];
        }

        // Validate UTF-8 encoding
        if (! mb_check_encoding($text, 'UTF-8')) {
            throw new AlgorithmException(
                'Input text must be valid UTF-8 encoded',
                SegmentationException::INPUT_INVALID_ENCODING
            );
        }

        // Optimize dictionary-specific settings
        $this->optimizeForDictionary($dictionary);

        // Matches are only valid for the dictionary as it is right now, which
        // may differ from the previous call (other instance, or words added)
        $this->matchCache = [];

        $chars = mb_str_split($this->preProcessText($text), 1, 'UTF-8');

        return $this->segmentChars($chars, $dictionary);
    }

    /**
     * Segment a pre-split character array
     *
     * @param  array<int, string>  $chars
     * @return array<int, string>
     */
    private function segmentChars(array $chars, DictionaryInterface $dictionary): array
    {
        $result = [];
        $count = count($chars);
        $position = 0;

        while ($position < $count) {
            $length = $this->matchLength($chars, $position, $dictionary);
            $token = implode('', array_slice($chars, $position, $length));

            // Whitespace runs collapse to a single space
            $result[] = $this->classifyCharacter($chars[$position]) === 'space' ? ' ' : $token;
            $position += $length;
        }

        return $this->postProcessResult($result);
    }

    /**
     * Number of characters the token starting at $position spans
     *
     * @param  array<int, string>  $chars
     */
    private function matchLength(array $chars, int $position, DictionaryInterface $dictionary): int
    {
        return match ($this->classifyCharacter($chars[$position])) {
            'thai' => $this->thaiMatchLength($chars, $position, $dictionary),
            'english' => $this->runLength($chars, $position, '/^[a-zA-Z0-9\-_.@]$/u', '.-_@'),
            'number' => $this->runLength($chars, $position, '/^[0-9\x{0E50}-\x{0E59}.,]$/u', '.,'),
            'space' => $this->runLength($chars, $position, '/^\s$/u', ''),
            default => 1,
        };
    }

    /**
     * Longest dictionary word at $position that ends on a cluster boundary,
     * or the length of one character cluster when no word matches
     *
     * @param  array<int, string>  $chars
     */
    private function thaiMatchLength(array $chars, int $position, DictionaryInterface $dictionary): int
    {
        $count = count($chars);
        $window = min($this->maxWordLength, $count - $position);

        // The match depends on the window plus the character right after it
        $cacheKey = implode('', array_slice($chars, $position, $window + 1));
        if (isset($this->matchCache[$cacheKey])) {
            return $this->matchCache[$cacheKey];
        }

        $best = 0;
        $candidate = '';
        for ($length = 1; $length <= $window; $length++) {
            $candidate .= $chars[$position + $length - 1];

            if ($this->isClusterBoundary($chars, $position + $length) && $dictionary->contains($candidate)) {
                $best = $length;
            }
        }

        if ($best === 0) {
            // Not cached: a cluster can reach past the characters in the key
            return $this->clusterLength($chars, $position);
        }

        $this->cacheMatch($cacheKey, $best);

        return $best;
    }

    /**
     * Whether a token may end right before index $end
     *
     * @param  array<int, string>  $chars
     */
    private function isClusterBoundary(array $chars, int $end): bool
    {
        if ($this->isLeadingVowel(mb_ord($chars[$end - 1], 'UTF-8'))) {
            return false;
        }

        return $end >= count($chars) || ! isset(self::THAI_ATTACHING[mb_ord($chars[$end], 'UTF-8')]);
    }

    /**
     * Length of the Thai character cluster starting at $position: any leading
     * vowels with the consonant they precede, or one base character, then
     * every attaching mark or vowel
     *
     * @param  array<int, string>  $chars
     */
    private function clusterLength(array $chars, int $position): int
    {
        $count = count($chars);
        $end = $position + 1;

        if ($this->isLeadingVowel(mb_ord($chars[$position], 'UTF-8'))) {
            // A doubled leading vowel ("เเ" typed for "แ") stays in one cluster
            while ($end < $count && $this->isLeadingVowel(mb_ord($chars[$end], 'UTF-8'))) {
                $end++;
            }

            if ($end < $count && $this->isThaiConsonant($chars[$end])) {
                $end++;
            }
        }

        while ($end < $count && isset(self::THAI_ATTACHING[mb_ord($chars[$end], 'UTF-8')])) {
            $end++;
        }

        return $end - $position;
    }

    /**
     * Length of a run of characters matching $pattern, without trailing
     * characters listed in $trailingTrim (so "example.com." keeps its final
     * period as a separate token)
     *
     * @param  array<int, string>  $chars
     */
    private function runLength(array $chars, int $position, string $pattern, string $trailingTrim): int
    {
        $count = count($chars);
        $end = $position;

        while ($end < $count && preg_match($pattern, $chars[$end])) {
            $end++;
        }

        while ($end - $position > 1 && $trailingTrim !== '' && str_contains($trailingTrim, $chars[$end - 1])) {
            $end--;
        }

        return max(1, $end - $position);
    }

    /**
     * Classify character type
     */
    private function classifyCharacter(string $char): string
    {
        $codePoint = mb_ord($char, 'UTF-8');

        if ($codePoint >= self::THAI_DIGIT_START && $codePoint <= self::THAI_DIGIT_END) {
            return 'number';
        }

        if ($codePoint >= self::THAI_RANGE_START && $codePoint <= self::THAI_RANGE_END) {
            return 'thai';
        }

        if (preg_match('/[a-zA-Z]/', $char)) {
            return 'english';
        }

        if (preg_match('/[0-9]/', $char)) {
            return 'number';
        }

        if (preg_match('/\s/u', $char)) {
            return 'space';
        }

        return 'other';
    }

    private function isThaiConsonant(string $char): bool
    {
        $codePoint = mb_ord($char, 'UTF-8');

        return $codePoint >= self::THAI_CONSONANT_START && $codePoint <= self::THAI_CONSONANT_END;
    }

    private function isLeadingVowel(int $codePoint): bool
    {
        return $codePoint >= self::THAI_LEADING_VOWEL_START && $codePoint <= self::THAI_LEADING_VOWEL_END;
    }

    /**
     * Optimize settings based on dictionary type and size
     */
    private function optimizeForDictionary(DictionaryInterface $dictionary): void
    {
        // Adjust max word length based on dictionary if method available
        if (method_exists($dictionary, 'getMaxWordLength')) {
            $this->maxWordLength = max(1, $dictionary->getMaxWordLength());
        }
    }

    /**
     * Pre-process text for better segmentation
     */
    private function preProcessText(string $text): string
    {
        // Normalize whitespace
        $text = preg_replace('/\s+/u', ' ', $text);

        // Trim leading/trailing whitespace
        return trim($text);
    }

    /**
     * Post-process segmentation result
     */
    private function postProcessResult(array $result): array
    {
        // Remove empty strings
        $result = array_filter($result, fn ($word) => $word !== '');

        // Merge consecutive spaces
        $merged = [];
        $pendingSpace = false;

        foreach ($result as $word) {
            if ($word === ' ') {
                $pendingSpace = true;
            } else {
                if ($pendingSpace && ! empty($merged)) {
                    $merged[] = ' ';
                    $pendingSpace = false;
                }
                $merged[] = $word;
            }
        }

        return array_values($merged);
    }

    /**
     * Cache a match result to avoid redundant lookups
     */
    private function cacheMatch(string $key, int $length): void
    {
        if (count($this->matchCache) >= self::MAX_CACHE_SIZE) {
            // Remove oldest half of cache
            $this->matchCache = array_slice($this->matchCache, self::MAX_CACHE_SIZE / 2, null, true);
        }

        $this->matchCache[$key] = $length;
    }

    /**
     * Clear match cache
     */
    public function clearCache(): void
    {
        $this->matchCache = [];
    }

    /**
     * Get cache statistics
     */
    public function getCacheStats(): array
    {
        return [
            'cache_size' => count($this->matchCache),
            'max_cache_size' => self::MAX_CACHE_SIZE,
        ];
    }
}
