<?php

declare(strict_types=1);

namespace Farzai\ThaiWord\Contracts;

/**
 * Interface for dictionary data sources using Adapter pattern
 *
 * Allows different data sources (files, URLs, databases) to be used
 * interchangeably for loading dictionary data.
 */
interface DictionarySourceInterface
{
    /**
     * Retrieve all words from the source
     *
     * @return array<int, string> Array of words
     *
     * @throws \Farzai\ThaiWord\Exceptions\DictionaryException If source cannot be read
     */
    public function getWords(): array;

    /**
     * Check if the source is available and accessible
     *
     * @return bool True if source is available
     */
    public function isAvailable(): bool;

    /**
     * Get metadata about the source (optional)
     *
     * @return array<string, mixed> Metadata array
     */
    public function getMetadata(): array;
}
