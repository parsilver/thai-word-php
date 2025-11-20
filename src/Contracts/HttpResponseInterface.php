<?php

declare(strict_types=1);

namespace Farzai\ThaiWord\Contracts;

/**
 * Interface for HTTP response
 *
 * Provides a simple abstraction over HTTP responses,
 * independent of the underlying HTTP client implementation.
 */
interface HttpResponseInterface
{
    /**
     * Get the HTTP status code
     *
     * @return int Status code (e.g., 200, 404, 500)
     */
    public function getStatusCode(): int;

    /**
     * Get the response body content
     *
     * @return string Response body as string
     */
    public function getContent(): string;

    /**
     * Get a specific header value
     *
     * @param  string  $name  Header name (case-insensitive)
     * @return string Header value or empty string if not found
     */
    public function getHeader(string $name): string;

    /**
     * Check if response is successful (2xx status code)
     *
     * @return bool True if status code is 2xx
     */
    public function isSuccessful(): bool;
}
