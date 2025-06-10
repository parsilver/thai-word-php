<?php

declare(strict_types=1);

namespace Farzai\ThaiWord\Dictionary\Sources;

use Farzai\ThaiWord\Contracts\DictionaryParserInterface;
use Farzai\ThaiWord\Contracts\DictionarySourceInterface;
use Farzai\ThaiWord\Exceptions\DictionaryException;
use Farzai\ThaiWord\Exceptions\SegmentationException;

/**
 * File Dictionary Source using Adapter pattern
 *
 * Handles loading dictionary content from local files
 * and uses a parser to process the content into words.
 */
class FileDictionarySource implements DictionarySourceInterface
{
    public function __construct(
        private readonly string $filePath,
        private readonly DictionaryParserInterface $parser
    ) {}

    public function getWords(): array
    {
        if (! $this->isAvailable()) {
            throw new DictionaryException(
                "Dictionary file not found: {$this->filePath}",
                SegmentationException::DICTIONARY_FILE_NOT_FOUND
            );
        }

        $content = file_get_contents($this->filePath);
        if ($content === false) {
            throw new DictionaryException(
                "Failed to read dictionary file: {$this->filePath}",
                SegmentationException::DICTIONARY_INVALID_FORMAT
            );
        }

        return $this->parser->parse($content);
    }

    public function isAvailable(): bool
    {
        return file_exists($this->filePath) && is_readable($this->filePath);
    }

    public function getMetadata(): array
    {
        $metadata = [
            'file_path' => $this->filePath,
            'parser_type' => $this->parser->getType(),
            'exists' => $this->isAvailable(),
        ];

        if ($this->isAvailable()) {
            $stat = stat($this->filePath);
            $metadata['size'] = $stat['size'] ?? null;
            $metadata['modified'] = $stat['mtime'] ? new \DateTimeImmutable("@{$stat['mtime']}") : null;
        }

        return $metadata;
    }
}
