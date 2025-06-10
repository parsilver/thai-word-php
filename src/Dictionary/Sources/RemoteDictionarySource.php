<?php

declare(strict_types=1);

namespace Farzai\ThaiWord\Dictionary\Sources;

use Farzai\ThaiWord\Contracts\DictionaryParserInterface;
use Farzai\ThaiWord\Contracts\DictionarySourceInterface;
use Farzai\ThaiWord\Exceptions\DictionaryException;
use Farzai\ThaiWord\Exceptions\SegmentationException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * Remote Dictionary Source using Adapter pattern
 *
 * Handles downloading dictionary content from remote URLs
 * and uses a parser to process the content into words.
 */
class RemoteDictionarySource implements DictionarySourceInterface
{
    private array $metadata = [];

    public function __construct(
        private readonly string $url,
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly DictionaryParserInterface $parser,
        private readonly int $timeout = 30,
        private readonly array $headers = []
    ) {}

    public function getWords(): array
    {
        $content = $this->downloadContent();

        return $this->parser->parse($content);
    }

    public function isAvailable(): bool
    {
        // Validate URL format
        if (! filter_var($this->url, FILTER_VALIDATE_URL)) {
            return false;
        }

        try {
            // Try to make a HEAD request to check availability
            $request = $this->requestFactory->createRequest('HEAD', $this->url);
            $request = $this->addHeaders($request);

            $response = $this->httpClient->sendRequest($request);

            return $response->getStatusCode() >= 200 && $response->getStatusCode() < 400;
        } catch (ClientExceptionInterface) {
            return false;
        }
    }

    public function getMetadata(): array
    {
        return [
            'url' => $this->url,
            'parser_type' => $this->parser->getType(),
            'timeout' => $this->timeout,
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
            // Create HTTP request
            $request = $this->requestFactory->createRequest('GET', $this->url);
            $request = $this->addHeaders($request);

            // Send request
            $response = $this->httpClient->sendRequest($request);

            // Check response status
            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                throw new DictionaryException(
                    "HTTP error {$response->getStatusCode()} when downloading from: {$this->url}",
                    SegmentationException::DICTIONARY_DOWNLOAD_FAILED
                );
            }

            // Get response content
            $content = $response->getBody()->getContents();

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
                'content_type' => $response->getHeaderLine('Content-Type'),
            ];

            return $content;

        } catch (ClientExceptionInterface $e) {
            throw new DictionaryException(
                "Failed to download dictionary from: {$this->url}. Error: {$e->getMessage()}",
                SegmentationException::DICTIONARY_DOWNLOAD_FAILED,
                $e
            );
        }
    }

    /**
     * Add headers to the HTTP request
     *
     * @param  \Psr\Http\Message\RequestInterface  $request
     * @return \Psr\Http\Message\RequestInterface
     */
    private function addHeaders($request)
    {
        // Add default headers
        $defaultHeaders = [
            'User-Agent' => 'Thai-Word-PHP/2.0 (+https://github.com/parsilver/thai-word-php)',
            'Accept' => 'text/plain, text/*, */*',
            'Connection' => 'close',
        ];

        // Merge with custom headers
        $allHeaders = array_merge($defaultHeaders, $this->headers);

        foreach ($allHeaders as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $request;
    }
}
