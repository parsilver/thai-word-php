<?php

declare(strict_types=1);

namespace Farzai\ThaiWord\Contracts;

/**
 * Interface for dictionary content parsers
 *
 * Separates parsing logic from data source handling,
 * allowing different parsing strategies for different dictionary formats.
 */
interface DictionaryParserInterface
{
    /**
     * Parse raw dictionary content into an array of words
     *
     * @param  string  $content  Raw dictionary content
     * @return array<int, string> Array of processed words
     *
     * @throws \Farzai\ThaiWord\Exceptions\DictionaryException If content format is invalid
     */
    public function parse(string $content): array;

    /**
     * Check if the parser supports the given content format
     *
     * @param  string  $content  Raw content to check
     * @return bool True if parser supports this format
     */
    public function supports(string $content): bool;

    /**
     * Get the parser type identifier
     *
     * @return string Parser type (e.g., 'libreoffice', 'plain_text', 'json')
     */
    public function getType(): string;
}
