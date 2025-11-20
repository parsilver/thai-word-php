<?php

use Farzai\ThaiWord\Contracts\HttpClientInterface;
use Farzai\ThaiWord\Exceptions\MissingDependencyException;
use Farzai\ThaiWord\Http\HttpClientFactory;

describe('HttpClientFactory', function () {

    it('checks if HTTP client is available', function () {
        // Since we have HTTP dependencies in require-dev, they should be available
        expect(HttpClientFactory::isHttpClientAvailable())->toBeTrue();
    });

    it('creates HTTP client when dependencies are available', function () {
        $client = HttpClientFactory::create();

        expect($client)->toBeInstanceOf(HttpClientInterface::class);
    });

    it('creates HTTP client with custom timeout', function () {
        $client = HttpClientFactory::create(60);

        expect($client)->toBeInstanceOf(HttpClientInterface::class);
    });

    it('createIfAvailable returns client when dependencies exist', function () {
        $client = HttpClientFactory::createIfAvailable();

        expect($client)->toBeInstanceOf(HttpClientInterface::class);
    });

    it('getMissingDependencies returns empty array when all deps available', function () {
        $missing = HttpClientFactory::getMissingDependencies();

        expect($missing)->toBeArray();
        expect($missing)->toBeEmpty();
    });
});
