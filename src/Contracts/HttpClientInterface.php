<?php

declare(strict_types=1);

namespace Farzai\ThaiWord\Contracts;

/**
 * Interface for HTTP client operations using Adapter pattern
 *
 * Abstracts HTTP operations to allow different implementations
 * (PSR-18, Guzzle, Symfony HttpClient, etc.) to be used interchangeably.
 */
interface HttpClientInterface
{
    /**
     * Send a GET request to the specified URL
     *
     * @param  string  $url  The URL to fetch
     * @param  array<string, string>  $headers  Optional headers
     *
     * @throws \Farzai\ThaiWord\Exceptions\HttpException If request fails
     */
    public function get(string $url, array $headers = []): HttpResponseInterface;

    /**
     * Send a HEAD request to the specified URL
     *
     * @param  string  $url  The URL to check
     * @param  array<string, string>  $headers  Optional headers
     *
     * @throws \Farzai\ThaiWord\Exceptions\HttpException If request fails
     */
    public function head(string $url, array $headers = []): HttpResponseInterface;

    /**
     * Check if a URL is accessible (returns 2xx or 3xx status)
     *
     * @param  string  $url  The URL to check
     * @return bool True if URL is accessible
     */
    public function isAvailable(string $url): bool;
}
