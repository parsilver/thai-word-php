<?php

declare(strict_types=1);

namespace Farzai\ThaiWord\Dictionary\Sources;

use Farzai\ThaiWord\Contracts\DictionaryParserInterface;
use Farzai\ThaiWord\Contracts\DictionarySourceInterface;
use Farzai\ThaiWord\Contracts\HttpClientInterface;
use Farzai\ThaiWord\Exceptions\DictionaryException;
use Farzai\ThaiWord\Exceptions\HttpException;
use Farzai\ThaiWord\Exceptions\SegmentationException;

/**
 * Remote Dictionary Source using Adapter pattern
 *
 * Handles downloading dictionary content from remote URLs
 * and uses a parser to process the content into words.
 * Uses HttpClientInterface for HTTP operations, making
 * HTTP dependencies optional.
 */
class RemoteDictionarySource implements DictionarySourceInterface
{
    private array $metadata = [];

    public function __construct(
        private readonly string $url,
        private readonly HttpClientInterface $httpClient,
        private readonly DictionaryParserInterface $parser,
        private readonly array $headers = []
    ) {}

    public function getWords(): array
    {
        $content = $this->downloadContent();

        return $this->parser->parse($content);
    }

    public function isAvailable(): bool
    {
        return $this->httpClient->isAvailable($this->url);
    }

    public function getMetadata(): array
    {
        return [
            'url' => $this->url,
            'parser_type' => $this->parser->getType(),
            'headers' => $this->headers,
            'last_download' => $this->metadata['last_download'] ?? null,
            'content_length' => $this->metadata['content_length'] ?? null,
            'content_type' => $this->metadata['content_type'] ?? null,
        ];
    }

    /**
     * Download content from the remote URL
     *
     * @return string Downloaded content
     *
     * @throws DictionaryException If download fails
     */
    private function downloadContent(): string
    {
        // Validate URL
        if (! filter_var($this->url, FILTER_VALIDATE_URL)) {
            throw new DictionaryException(
                "Invalid URL provided: {$this->url}",
                SegmentationException::DICTIONARY_INVALID_SOURCE
            );
        }

        try {
            // Send HTTP GET request
            $response = $this->httpClient->get($this->url, $this->headers);

            // Check response status
            if (! $response->isSuccessful()) {
                throw new DictionaryException(
                    "HTTP error {$response->getStatusCode()} when downloading from: {$this->url}",
                    SegmentationException::DICTIONARY_DOWNLOAD_FAILED
                );
            }

            // Get response content
            $content = $response->getContent();

            if (empty($content)) {
                throw new DictionaryException(
                    "Empty response received from: {$this->url}",
                    SegmentationException::DICTIONARY_EMPTY
                );
            }

            // Store metadata
            $this->metadata = [
                'last_download' => new \DateTimeImmutable,
                'content_length' => strlen($content),
                'content_type' => $response->getHeader('Content-Type'),
            ];

            return $content;

        } catch (HttpException $e) {
            throw new DictionaryException(
                "Failed to download dictionary from: {$this->url}. Error: {$e->getMessage()}",
                SegmentationException::DICTIONARY_DOWNLOAD_FAILED,
                $e
            );
        }
    }

}
