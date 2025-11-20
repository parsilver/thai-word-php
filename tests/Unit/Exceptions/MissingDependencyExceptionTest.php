<?php

use Farzai\ThaiWord\Exceptions\MissingDependencyException;
use Farzai\ThaiWord\Exceptions\SegmentationException;

describe('MissingDependencyException', function () {

    it('creates exception for HTTP client with helpful message', function () {
        $exception = MissingDependencyException::forHttpClient();

        expect($exception)->toBeInstanceOf(MissingDependencyException::class);
        expect($exception->getMessage())->toContain('HTTP transport library is required');
        expect($exception->getMessage())->toContain('composer require');
        expect($exception->getMessage())->toContain('farzai/transport');
        expect($exception->getCode())->toBe(SegmentationException::CONFIG_MISSING_REQUIRED);
    });

    it('creates exception for specific class', function () {
        $exception = MissingDependencyException::forClass('Psr\Http\Client\ClientInterface', 'psr/http-client');

        expect($exception)->toBeInstanceOf(MissingDependencyException::class);
        expect($exception->getMessage())->toContain('Psr\Http\Client\ClientInterface');
        expect($exception->getMessage())->toContain('psr/http-client');
        expect($exception->getCode())->toBe(SegmentationException::CONFIG_MISSING_REQUIRED);
    });

    it('provides installation instructions', function () {
        $exception = MissingDependencyException::forHttpClient();

        expect($exception->getMessage())->toContain('composer require');
    });

    it('suggests alternative approaches', function () {
        $exception = MissingDependencyException::forHttpClient();

        expect($exception->getMessage())->toContain('local dictionary files');
    });
});
