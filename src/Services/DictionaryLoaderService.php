<?php

declare(strict_types=1);

namespace Farzai\ThaiWord\Services;

use Farzai\ThaiWord\Contracts\DictionarySourceInterface;
use Farzai\ThaiWord\Dictionary\Config\DictionaryUrls;
use Farzai\ThaiWord\Dictionary\Factories\DictionarySourceFactory;
use Farzai\ThaiWord\Exceptions\DictionaryException;

/**
 * Centralized service for loading dictionaries from various sources.
 *
 * This service provides a focused interface for dictionary loading operations:
 * - Loading from URLs with automatic detection
 * - Loading from local files with validation
 * - Loading from dictionary sources with error handling
 * - Source availability checking
 *
 * Follows SRP by focusing only on loading operations, not source creation.
 */
class DictionaryLoaderService
{
    public function __construct(
        private readonly DictionarySourceFactory $sourceFactory
    ) {}

    /**
     * Load words from a URL.
     *
     * @param  string  $url  URL to load from
     * @param  int  $timeout  Request timeout in seconds
     * @param  string  $parserType  Parser type for processing the content
     * @return array<int, string> Array of words
     *
     * @throws DictionaryException If URL cannot be loaded
     */
    public function loadFromUrl(string $url, int $timeout = 30, string $parserType = 'main'): array
    {
        $source = $this->sourceFactory->create('url', $url, [
            'timeout' => $timeout,
            'parser_type' => $parserType,
        ]);

        return $this->loadFromDictionarySource($source);
    }

    /**
     * Load words from a local file.
     *
     * @param  string  $filePath  Path to the dictionary file
     * @param  string  $parserType  Parser type for processing the content
     * @return array<int, string> Array of words
     *
     * @throws DictionaryException If file cannot be read
     */
    public function loadFromFile(string $filePath, string $parserType = 'plain'): array
    {
        $source = $this->sourceFactory->create('file', $filePath, [
            'parser_type' => $parserType,
        ]);

        return $this->loadFromDictionarySource($source);
    }

    /**
     * Load the official LibreOffice Thai dictionary.
     *
     * @param  string  $type  Dictionary type (main, typos_translit, typos_common)
     * @param  int  $timeout  Request timeout in seconds
     * @return array<int, string> Array of words
     *
     * @throws DictionaryException If loading fails
     */
    public function loadLibreOfficeThaiDictionary(string $type = 'main', int $timeout = 30): array
    {
        return $this->loadFromUrl(DictionaryUrls::getLibreOfficeUrl($type), $timeout);
    }

    /**
     * Load words from a dictionary source with validation.
     *
     * @param  DictionarySourceInterface  $source  The dictionary source to load from
     * @return array<int, string> Array of words
     *
     * @throws DictionaryException If the source is not available or loading fails
     */
    public function loadFromDictionarySource(DictionarySourceInterface $source): array
    {
        if (! $source->isAvailable()) {
            throw new DictionaryException(
                'Dictionary source is not available'
            );
        }

        return $source->getWords();
    }

    /**
     * Determine if a string is a valid URL.
     *
     * @param  string  $string  String to check
     * @return bool True if string is a valid URL
     */
    public function isUrl(string $string): bool
    {
        return filter_var($string, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Load from a source (URL or file path) with automatic detection.
     *
     * @param  string  $source  Source path or URL
     * @param  int  $timeout  Request timeout for URLs
     * @param  string  $parserType  Parser type for processing the content
     * @return array<int, string> Array of words
     *
     * @throws DictionaryException If source cannot be loaded
     */
    public function load(string $source, int $timeout = 30, string $parserType = 'plain'): array
    {
        if ($this->isUrl($source)) {
            return $this->loadFromUrl($source, $timeout, $parserType);
        }

        return $this->loadFromFile($source, $parserType);
    }
}
