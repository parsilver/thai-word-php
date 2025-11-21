<?php

declare(strict_types=1);

namespace Farzai\ThaiWord\Dictionary\Sources;

use Farzai\ThaiWord\Contracts\DictionaryParserInterface;
use Farzai\ThaiWord\Contracts\DictionarySourceInterface;
use Farzai\ThaiWord\Exceptions\DictionaryException;
use Farzai\ThaiWord\Exceptions\SegmentationException;
use Farzai\Transport\Exceptions\ClientException;
use Farzai\Transport\Exceptions\NetworkException;
use Farzai\Transport\Exceptions\RetryExhaustedException;
use Farzai\Transport\Exceptions\ServerException;
use Farzai\Transport\Exceptions\TimeoutException;
use Farzai\Transport\RequestBuilder;
use Farzai\Transport\Transport;
use Psr\Http\Message\ResponseInterface;

/**
 * Remote Dictionary Source using Transport
 *
 * Handles downloading dictionary content from remote URLs
 * and uses a parser to process the content into words.
 * Uses farzai/transport for HTTP operations with retry logic.
 */
class RemoteDictionarySource implements DictionarySourceInterface
{
    private array $metadata = [];

    public function __construct(
        private readonly string $url,
        private readonly Transport $transport,
        private readonly DictionaryParserInterface $parser
    ) {}

    public function getWords(): array
    {
        $content = $this->downloadContent();

        return $this->parser->parse($content);
    }

    public function isAvailable(): bool
    {
        if (! filter_var($this->url, FILTER_VALIDATE_URL)) {
            return false;
        }

        try {
            $request = RequestBuilder::head($this->url)->build();
            $response = $this->transport->sendRequest($request);

            $statusCode = $response->getStatusCode();

            return $statusCode >= 200 && $statusCode < 300;
        } catch (\Throwable) {
            return false;
        }
    }

    public function getMetadata(): array
    {
        return [
            'url' => $this->url,
            'parser_type' => $this->parser->getType(),
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
        $this->validateUrl();

        try {
            // Send HTTP GET request (with automatic retry from transport)
            $request = RequestBuilder::get($this->url)->build();
            $response = $this->transport->sendRequest($request);

            // Check response status
            $statusCode = $response->getStatusCode();
            if ($statusCode < 200 || $statusCode >= 300) {
                throw new DictionaryException(
                    "HTTP error {$statusCode} when downloading from: {$this->url}",
                    SegmentationException::DICTIONARY_DOWNLOAD_FAILED
                );
            }

            // Get response content
            $content = (string) $response->getBody();

            if (empty($content)) {
                throw new DictionaryException(
                    "Empty response received from: {$this->url}",
                    SegmentationException::DICTIONARY_EMPTY
                );
            }

            $this->storeMetadata($response, $content);

            return $content;

        } catch (ClientException $e) {
            throw new DictionaryException(
                "Client error (HTTP {$e->getStatusCode()}) downloading from {$this->url}: {$e->getMessage()}",
                SegmentationException::DICTIONARY_DOWNLOAD_FAILED,
                $e
            );
        } catch (ServerException $e) {
            throw new DictionaryException(
                "Server error (HTTP {$e->getStatusCode()}) downloading from {$this->url}: {$e->getMessage()}",
                SegmentationException::DICTIONARY_DOWNLOAD_FAILED,
                $e
            );
        } catch (NetworkException $e) {
            throw new DictionaryException(
                "Network error downloading from {$this->url}: {$e->getMessage()}",
                SegmentationException::DICTIONARY_DOWNLOAD_FAILED,
                $e
            );
        } catch (TimeoutException $e) {
            throw new DictionaryException(
                "Timeout downloading from {$this->url}: {$e->getMessage()}",
                SegmentationException::DICTIONARY_DOWNLOAD_FAILED,
                $e
            );
        } catch (RetryExhaustedException $e) {
            throw new DictionaryException(
                "Download failed after {$e->getAttempts()} attempts from {$this->url}: {$e->getMessage()}",
                SegmentationException::DICTIONARY_DOWNLOAD_FAILED,
                $e
            );
        } catch (\Throwable $e) {
            // Generic catch-all for any other exceptions (PSR-18 client exceptions, etc.)
            throw new DictionaryException(
                "Failed to download dictionary from: {$this->url}. Error: {$e->getMessage()}",
                SegmentationException::DICTIONARY_DOWNLOAD_FAILED,
                $e
            );
        }
    }

    /**
     * Validate the URL format
     *
     * @throws DictionaryException If URL is invalid
     */
    private function validateUrl(): void
    {
        if (! filter_var($this->url, FILTER_VALIDATE_URL)) {
            throw new DictionaryException(
                "Invalid URL provided: {$this->url}",
                SegmentationException::DICTIONARY_INVALID_SOURCE
            );
        }
    }

    /**
     * Store metadata from the response
     */
    private function storeMetadata(ResponseInterface $response, string $content): void
    {
        $this->metadata = [
            'last_download' => new \DateTimeImmutable,
            'content_length' => strlen($content),
            'content_type' => $response->getHeaderLine('Content-Type'),
        ];
    }
}
