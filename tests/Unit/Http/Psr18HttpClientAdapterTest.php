<?php

use Farzai\ThaiWord\Contracts\HttpClientInterface;
use Farzai\ThaiWord\Contracts\HttpResponseInterface;
use Farzai\ThaiWord\Exceptions\HttpException;
use Farzai\ThaiWord\Http\Psr18HttpClientAdapter;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;

describe('Psr18HttpClientAdapter', function () {

    it('implements HttpClientInterface', function () {
        $mockHandler = new MockHandler([]);
        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);
        $requestFactory = new HttpFactory;

        $adapter = new Psr18HttpClientAdapter($httpClient, $requestFactory);

        expect($adapter)->toBeInstanceOf(HttpClientInterface::class);
    });

    it('successfully sends GET request', function () {
        $mockHandler = new MockHandler([
            new Response(200, ['Content-Type' => 'text/plain'], 'Test content'),
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);
        $requestFactory = new HttpFactory;

        $adapter = new Psr18HttpClientAdapter($httpClient, $requestFactory);
        $response = $adapter->get('https://example.com/test.txt');

        expect($response)->toBeInstanceOf(HttpResponseInterface::class);
        expect($response->getStatusCode())->toBe(200);
        expect($response->getContent())->toBe('Test content');
        expect($response->getHeader('Content-Type'))->toBe('text/plain');
        expect($response->isSuccessful())->toBeTrue();
    });

    it('successfully sends HEAD request', function () {
        $mockHandler = new MockHandler([
            new Response(200, ['Content-Type' => 'text/plain']),
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);
        $requestFactory = new HttpFactory;

        $adapter = new Psr18HttpClientAdapter($httpClient, $requestFactory);
        $response = $adapter->head('https://example.com/test.txt');

        expect($response)->toBeInstanceOf(HttpResponseInterface::class);
        expect($response->getStatusCode())->toBe(200);
        expect($response->isSuccessful())->toBeTrue();
    });

    it('checks if URL is available', function () {
        $mockHandler = new MockHandler([
            new Response(200),
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);
        $requestFactory = new HttpFactory;

        $adapter = new Psr18HttpClientAdapter($httpClient, $requestFactory);

        expect($adapter->isAvailable('https://example.com'))->toBeTrue();
    });

    it('returns false for invalid URL', function () {
        $mockHandler = new MockHandler([]);
        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);
        $requestFactory = new HttpFactory;

        $adapter = new Psr18HttpClientAdapter($httpClient, $requestFactory);

        expect($adapter->isAvailable('not-a-valid-url'))->toBeFalse();
    });

    it('throws HttpException on request failure', function () {
        $mockHandler = new MockHandler([
            new \GuzzleHttp\Exception\ConnectException(
                'Connection failed',
                new \GuzzleHttp\Psr7\Request('GET', 'https://example.com')
            ),
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);
        $requestFactory = new HttpFactory;

        $adapter = new Psr18HttpClientAdapter($httpClient, $requestFactory);

        expect(fn () => $adapter->get('https://example.com'))
            ->toThrow(HttpException::class);
    });

    it('throws HttpException for invalid URL in GET request', function () {
        $mockHandler = new MockHandler([]);
        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);
        $requestFactory = new HttpFactory;

        $adapter = new Psr18HttpClientAdapter($httpClient, $requestFactory);

        expect(fn () => $adapter->get('not-a-valid-url'))
            ->toThrow(HttpException::class, 'Invalid URL');
    });

    it('adds custom headers to requests', function () {
        $mockHandler = new MockHandler([
            new Response(200, [], 'Success'),
        ]);
        $handlerStack = HandlerStack::create($mockHandler);
        $httpClient = new Client(['handler' => $handlerStack]);
        $requestFactory = new HttpFactory;

        $adapter = new Psr18HttpClientAdapter($httpClient, $requestFactory);
        $response = $adapter->get('https://example.com', ['X-Custom-Header' => 'TestValue']);

        expect($response->isSuccessful())->toBeTrue();
    });
});
