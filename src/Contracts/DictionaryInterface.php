<?php

namespace Farzai\ThaiWord\Contracts;

use Farzai\ThaiWord\Exceptions\DictionaryException;

interface DictionaryInterface
{
    /**
     * Load words from a source (file, database, etc.)
     *
     * @param  string  $source  The source to load from
     *
     * @throws DictionaryException
     */
    public function load(string $source): void;

    /**
     * Add a word to the dictionary
     *
     * @param  string  $word  The word to add
     * @return bool True if word was added, false if already exists
     *
     * @throws DictionaryException
     */
    public function add(string $word): bool;

    /**
     * Remove a word from the dictionary
     *
     * @param  string  $word  The word to remove
     * @return bool True if word was removed, false if not found
     *
     * @throws DictionaryException
     */
    public function remove(string $word): bool;

    /**
     * Check if a word exists in the dictionary
     *
     * @param  string  $word  The word to check
     * @return bool True if word exists, false otherwise
     */
    public function contains(string $word): bool;

    /**
     * Get all words in the dictionary
     *
     * @return array<int, string> Array of all words
     */
    public function getWords(): array;
}
