<?php

declare(strict_types=1);

namespace Farzai\ThaiWord\Dictionary\Parsers;

use Farzai\ThaiWord\Contracts\DictionaryParserInterface;
use Farzai\ThaiWord\Exceptions\DictionaryException;
use Farzai\ThaiWord\Exceptions\SegmentationException;

/**
 * Parser for LibreOffice dictionary formats
 *
 * Handles the specific format used by LibreOffice Thai dictionaries,
 * including main dictionaries and typos dictionaries with different formats.
 */
class LibreOfficeParser implements DictionaryParserInterface
{
    public const TYPE_MAIN = 'main';

    public const TYPE_TYPOS_TRANSLIT = 'typos_translit';

    public const TYPE_TYPOS_COMMON = 'typos_common';

    public function __construct(
        private readonly string $type = self::TYPE_MAIN
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
            // Skip empty lines
            $trimmedLine = trim($line);
            if ($trimmedLine === '') {
                continue;
            }

            // Extract word based on dictionary type
            $word = $this->extractWord($trimmedLine);

            if ($word !== null && $this->isValidEntry($word)) {
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
        // Basic check for LibreOffice format patterns
        return ! empty($content) && mb_check_encoding($content, 'UTF-8');
    }

    public function getType(): string
    {
        return 'libreoffice_'.$this->type;
    }

    /**
     * Extract the main word from a dictionary line
     *
     * Handles entries with phonetic annotations like "word/ph:pronunciation"
     * and typos dictionaries with "typo->correction" format
     *
     * @param  string  $line  Raw dictionary line
     * @return string|null Extracted word or null if invalid
     */
    private function extractWord(string $line): ?string
    {
        $word = $line;

        // Handle different dictionary types
        switch ($this->type) {
            case self::TYPE_TYPOS_TRANSLIT:
            case self::TYPE_TYPOS_COMMON:
                // Handle typos format: "typo->correction" or "typo	correction"
                if (strpos($word, '->') !== false) {
                    // Split by arrow and return both parts
                    $parts = explode('->', $word, 2);

                    return trim($parts[0]).'->'.trim($parts[1]);
                } elseif (strpos($word, "\t") !== false) {
                    // Split by tab and return both parts
                    $parts = explode("\t", $word, 2);

                    return trim($parts[0]).'->'.trim($parts[1]);
                }
                break;

            case self::TYPE_MAIN:
            default:
                // Remove phonetic annotations (e.g., "/ph:pronunciation")
                $word = preg_replace('/\/ph:.*$/', '', $word) ?? '';
                break;
        }

        $word = trim($word);

        // Skip if empty after processing
        if ($word === '') {
            return null;
        }

        return $word;
    }

    /**
     * Validate if an entry is valid based on dictionary type
     *
     * @param  string  $entry  Entry to validate
     * @return bool True if valid entry
     */
    private function isValidEntry(string $entry): bool
    {
        // Check UTF-8 encoding
        if (! mb_check_encoding($entry, 'UTF-8')) {
            return false;
        }

        switch ($this->type) {
            case self::TYPE_TYPOS_TRANSLIT:
            case self::TYPE_TYPOS_COMMON:
                return $this->isValidTyposEntry($entry);

            case self::TYPE_MAIN:
            default:
                return $this->isValidMainEntry($entry);
        }
    }

    /**
     * Validate typos dictionary entry
     *
     * @param  string  $entry  Entry to validate
     * @return bool True if valid
     */
    private function isValidTyposEntry(string $entry): bool
    {
        // For typos dictionaries, validate typo->correction format
        if (strpos($entry, '->') === false) {
            return false;
        }

        $parts = explode('->', $entry, 2);
        if (count($parts) !== 2) {
            return false;
        }

        $typo = trim($parts[0]);
        $correction = trim($parts[1]);

        // Both parts should have Thai characters
        if (! preg_match('/[\x{0E00}-\x{0E7F}]/u', $typo) ||
            ! preg_match('/[\x{0E00}-\x{0E7F}]/u', $correction)) {
            return false;
        }

        // Check length constraints
        if (mb_strlen($typo, 'UTF-8') > 50 || mb_strlen($correction, 'UTF-8') > 50) {
            return false;
        }

        return true;
    }

    /**
     * Validate main dictionary entry
     *
     * @param  string  $entry  Entry to validate
     * @return bool True if valid
     */
    private function isValidMainEntry(string $entry): bool
    {
        // Check if word contains Thai characters
        // Thai Unicode range: U+0E00–U+0E7F
        if (! preg_match('/[\x{0E00}-\x{0E7F}]/u', $entry)) {
            return false;
        }

        // Skip words that are too short (single character) or too long
        $length = mb_strlen($entry, 'UTF-8');
        if ($length < 1 || $length > 50) {
            return false;
        }

        // Skip words with suspicious characters (numbers, excessive punctuation)
        if (preg_match('/[0-9]{3,}/', $entry)) {
            return false;
        }

        return true;
    }
}
