<?php

declare(strict_types=1);

namespace Farzai\ThaiWord\Http;

use Farzai\ThaiWord\Contracts\HttpResponseInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * HTTP Response implementation wrapping PSR-7 Response
 *
 * Adapts PSR-7 ResponseInterface to our HttpResponseInterface
 */
class Psr18HttpResponse implements HttpResponseInterface
{
    public function __construct(
        private readonly ResponseInterface $response
    ) {}

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    public function getContent(): string
    {
        return $this->response->getBody()->getContents();
    }

    public function getHeader(string $name): string
    {
        return $this->response->getHeaderLine($name);
    }

    public function isSuccessful(): bool
    {
        $statusCode = $this->getStatusCode();

        return $statusCode >= 200 && $statusCode < 300;
    }
}
