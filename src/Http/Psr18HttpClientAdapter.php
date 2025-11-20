<?php

declare(strict_types=1);

namespace Farzai\ThaiWord\Http;

use Farzai\ThaiWord\Contracts\HttpClientInterface;
use Farzai\ThaiWord\Contracts\HttpResponseInterface;
use Farzai\ThaiWord\Exceptions\HttpException;
use Farzai\ThaiWord\Exceptions\MissingDependencyException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;

/**
 * PSR-18 HTTP Client Adapter
 *
 * Adapts PSR-18/PSR-17 interfaces to our HttpClientInterface.
 * Validates that required dependencies are installed on construction.
 *
 * Note: Timeout configuration should be done on the underlying HTTP client
 * before passing it to this adapter, as PSR-18 doesn't define a standard
 * timeout interface.
 */
class Psr18HttpClientAdapter implements HttpClientInterface
{
    private const DEFAULT_HEADERS = [
        'User-Agent' => 'Thai-Word-PHP (+https://github.com/parsilver/thai-word-php)',
        'Accept' => 'text/plain, text/*, */*',
        'Connection' => 'close',
    ];

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory
    ) {
        // Validate dependencies are available
        $this->validateDependencies();
    }

    public function get(string $url, array $headers = []): HttpResponseInterface
    {
        return $this->sendRequest('GET', $url, $headers);
    }

    public function head(string $url, array $headers = []): HttpResponseInterface
    {
        return $this->sendRequest('HEAD', $url, $headers);
    }

    public function isAvailable(string $url): bool
    {
        // Validate URL format
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        try {
            $response = $this->head($url);

            return $response->getStatusCode() >= 200 && $response->getStatusCode() < 400;
        } catch (HttpException) {
            return false;
        }
    }

    /**
     * Send HTTP request
     *
     * @param  string  $method  HTTP method (GET, HEAD, etc.)
     * @param  string  $url  Target URL
     * @param  array<string, string>  $headers  Custom headers
     *
     * @throws HttpException If request fails
     */
    private function sendRequest(string $method, string $url, array $headers): HttpResponseInterface
    {
        // Validate URL
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new HttpException("Invalid URL: {$url}");
        }

        try {
            // Create request
            $request = $this->requestFactory->createRequest($method, $url);

            // Add headers
            $allHeaders = array_merge(self::DEFAULT_HEADERS, $headers);
            foreach ($allHeaders as $name => $value) {
                $request = $request->withHeader($name, $value);
            }

            // Send request
            $response = $this->httpClient->sendRequest($request);

            return new Psr18HttpResponse($response);

        } catch (ClientExceptionInterface $e) {
            throw new HttpException(
                "HTTP request failed for {$method} {$url}: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    /**
     * Validate that required PSR dependencies are available
     *
     * @throws MissingDependencyException If dependencies are missing
     */
    private function validateDependencies(): void
    {
        $requiredClasses = [
            'Psr\Http\Client\ClientInterface' => 'psr/http-client',
            'Psr\Http\Message\RequestFactoryInterface' => 'psr/http-factory',
        ];

        foreach ($requiredClasses as $class => $package) {
            if (! interface_exists($class)) {
                throw MissingDependencyException::forClass($class, $package);
            }
        }
    }
}
