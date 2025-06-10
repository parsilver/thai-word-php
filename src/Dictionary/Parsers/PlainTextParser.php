<?php

declare(strict_types=1);

namespace Farzai\ThaiWord\Dictionary\Parsers;

use Farzai\ThaiWord\Contracts\DictionaryParserInterface;
use Farzai\ThaiWord\Exceptions\DictionaryException;
use Farzai\ThaiWord\Exceptions\SegmentationException;

/**
 * Parser for plain text dictionary formats
 *
 * Handles simple dictionary files with one word per line.
 * Provides basic validation for Thai words.
 */
class PlainTextParser implements DictionaryParserInterface
{
    public function __construct(
        private readonly bool $validateThai = true,
        private readonly int $minLength = 1,
        private readonly int $maxLength = 50
    ) {}

    public function parse(string $content): array
    {
        if (empty($content)) {
            throw new DictionaryException(
                'Dictionary content is empty',
                SegmentationException::DICTIONARY_EMPTY
            );
        }

        // Split content into lines
        $lines = preg_split('/\r\n|\r|\n/', $content);

        if ($lines === false) {
            throw new DictionaryException(
                'Failed to parse dictionary content',
                SegmentationException::DICTIONARY_INVALID_FORMAT
            );
        }

        $words = [];

        foreach ($lines as $line) {
            // Skip empty lines and comments
            $word = trim($line);
            if ($word === '' || str_starts_with($word, '#') || str_starts_with($word, '//')) {
                continue;
            }

            if ($this->isValidWord($word)) {
                $words[] = $word;
            }
        }

        // Remove duplicates and sort
        $words = array_values(array_unique($words));
        sort($words);

        return $words;
    }

    public function supports(string $content): bool
    {
        // Basic check for plain text format
        if (empty($content) || ! mb_check_encoding($content, 'UTF-8')) {
            return false;
        }

        // Check if content has simple line-based structure
        $lines = explode("\n", $content);
        $validLines = 0;

        foreach (array_slice($lines, 0, 10) as $line) { // Check first 10 lines
            $word = trim($line);
            if ($word === '' || str_starts_with($word, '#') || str_starts_with($word, '//')) {
                continue;
            }

            if ($this->isValidWord($word)) {
                $validLines++;
            }
        }

        return $validLines > 0;
    }

    public function getType(): string
    {
        return 'plain_text';
    }

    /**
     * Validate if a word is valid based on configuration
     *
     * @param  string  $word  Word to validate
     * @return bool True if valid
     */
    private function isValidWord(string $word): bool
    {
        // Check UTF-8 encoding
        if (! mb_check_encoding($word, 'UTF-8')) {
            return false;
        }

        // Check length constraints
        $length = mb_strlen($word, 'UTF-8');
        if ($length < $this->minLength || $length > $this->maxLength) {
            return false;
        }

        // Optional Thai character validation
        if ($this->validateThai) {
            // Check if word contains Thai characters
            // Thai Unicode range: U+0E00–U+0E7F
            if (! preg_match('/[\x{0E00}-\x{0E7F}]/u', $word)) {
                return false;
            }

            // Skip words with excessive numbers
            if (preg_match('/[0-9]{3,}/', $word)) {
                return false;
            }
        }

        return true;
    }
}
