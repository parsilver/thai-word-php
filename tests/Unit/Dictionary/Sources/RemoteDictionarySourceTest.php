<?php

use Farzai\ThaiWord\Contracts\DictionaryParserInterface;
use Farzai\ThaiWord\Contracts\HttpClientInterface;
use Farzai\ThaiWord\Contracts\HttpResponseInterface;
use Farzai\ThaiWord\Dictionary\Sources\RemoteDictionarySource;
use Farzai\ThaiWord\Exceptions\DictionaryException;
use Farzai\ThaiWord\Exceptions\HttpException;
use Farzai\ThaiWord\Exceptions\SegmentationException;

describe('RemoteDictionarySource', function () {
    beforeEach(function () {
        $this->httpClient = Mockery::mock(HttpClientInterface::class);
        $this->parser = Mockery::mock(DictionaryParserInterface::class);
        $this->response = Mockery::mock(HttpResponseInterface::class);
        $this->testUrl = 'https://example.com/dictionary.txt';
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('getWords', function () {
        it('can download and parse words from remote URL', function () {
            $content = "สวัสดี\nขอบคุณ\nสบายดี";
            $words = ['สวัสดี', 'ขอบคุณ', 'สบายดี'];

            $this->response->shouldReceive('isSuccessful')
                ->once()
                ->andReturn(true);

            $this->response->shouldReceive('getContent')
                ->once()
                ->andReturn($content);

            $this->response->shouldReceive('getStatusCode')
                ->never();

            $this->response->shouldReceive('getHeader')
                ->with('Content-Type')
                ->andReturn('text/plain; charset=utf-8');

            $this->httpClient->shouldReceive('get')
                ->once()
                ->with($this->testUrl, [])
                ->andReturn($this->response);

            $this->parser->shouldReceive('parse')
                ->once()
                ->with($content)
                ->andReturn($words);

            $source = new RemoteDictionarySource(
                $this->testUrl,
                $this->httpClient,
                $this->parser
            );

            $result = $source->getWords();

            expect($result)->toBe($words);
        });

        it('can pass custom headers to HTTP client', function () {
            $content = 'test';
            $customHeaders = [
                'User-Agent' => 'TestBot/1.0',
                'Accept-Language' => 'th-TH',
            ];

            $this->response->shouldReceive('isSuccessful')
                ->andReturn(true);

            $this->response->shouldReceive('getContent')
                ->andReturn($content);

            $this->response->shouldReceive('getHeader')
                ->with('Content-Type')
                ->andReturn('text/plain');

            $this->httpClient->shouldReceive('get')
                ->once()
                ->with($this->testUrl, $customHeaders)
                ->andReturn($this->response);

            $this->parser->shouldReceive('parse')
                ->with($content)
                ->andReturn(['test']);

            $source = new RemoteDictionarySource(
                $this->testUrl,
                $this->httpClient,
                $this->parser,
                $customHeaders
            );

            $source->getWords();
        });

        it('throws exception for invalid URL', function () {
            $invalidUrl = 'not-a-valid-url';

            $source = new RemoteDictionarySource(
                $invalidUrl,
                $this->httpClient,
                $this->parser
            );

            expect(fn () => $source->getWords())
                ->toThrow(
                    DictionaryException::class,
                    "Invalid URL provided: {$invalidUrl}",
                    SegmentationException::DICTIONARY_INVALID_SOURCE
                );
        });

        it('throws exception when HTTP request fails', function () {
            $this->response->shouldReceive('isSuccessful')
                ->once()
                ->andReturn(false);

            $this->response->shouldReceive('getStatusCode')
                ->once()
                ->andReturn(404);

            $this->httpClient->shouldReceive('get')
                ->once()
                ->with($this->testUrl, [])
                ->andReturn($this->response);

            $source = new RemoteDictionarySource(
                $this->testUrl,
                $this->httpClient,
                $this->parser
            );

            expect(fn () => $source->getWords())
                ->toThrow(
                    DictionaryException::class,
                    "HTTP error 404 when downloading from: {$this->testUrl}",
                    SegmentationException::DICTIONARY_DOWNLOAD_FAILED
                );
        });

        it('throws exception when response is empty', function () {
            $this->response->shouldReceive('isSuccessful')
                ->once()
                ->andReturn(true);

            $this->response->shouldReceive('getContent')
                ->once()
                ->andReturn('');

            $this->httpClient->shouldReceive('get')
                ->once()
                ->with($this->testUrl, [])
                ->andReturn($this->response);

            $source = new RemoteDictionarySource(
                $this->testUrl,
                $this->httpClient,
                $this->parser
            );

            expect(fn () => $source->getWords())
                ->toThrow(
                    DictionaryException::class,
                    "Empty response received from: {$this->testUrl}",
                    SegmentationException::DICTIONARY_EMPTY
                );
        });

        it('wraps HTTP exceptions in DictionaryException', function () {
            $httpException = new HttpException('Network error');

            $this->httpClient->shouldReceive('get')
                ->once()
                ->with($this->testUrl, [])
                ->andThrow($httpException);

            $source = new RemoteDictionarySource(
                $this->testUrl,
                $this->httpClient,
                $this->parser
            );

            expect(fn () => $source->getWords())
                ->toThrow(
                    DictionaryException::class,
                    "Failed to download dictionary from: {$this->testUrl}. Error: Network error",
                    SegmentationException::DICTIONARY_DOWNLOAD_FAILED
                );
        });

        it('handles various HTTP error codes', function () {
            $errorCodes = [400, 403, 404, 500, 503];

            foreach ($errorCodes as $code) {
                $this->response->shouldReceive('isSuccessful')
                    ->once()
                    ->andReturn(false);

                $this->response->shouldReceive('getStatusCode')
                    ->once()
                    ->andReturn($code);

                $this->httpClient->shouldReceive('get')
                    ->once()
                    ->andReturn($this->response);

                $source = new RemoteDictionarySource(
                    $this->testUrl,
                    $this->httpClient,
                    $this->parser
                );

                expect(fn () => $source->getWords())
                    ->toThrow(DictionaryException::class);

                Mockery::close();
                $this->httpClient = Mockery::mock(HttpClientInterface::class);
                $this->parser = Mockery::mock(DictionaryParserInterface::class);
                $this->response = Mockery::mock(HttpResponseInterface::class);
            }
        });
    });

    describe('isAvailable', function () {
        it('delegates to HTTP client isAvailable method', function () {
            $this->httpClient->shouldReceive('isAvailable')
                ->once()
                ->with($this->testUrl)
                ->andReturn(true);

            $source = new RemoteDictionarySource(
                $this->testUrl,
                $this->httpClient,
                $this->parser
            );

            $result = $source->isAvailable();

            expect($result)->toBeTrue();
        });

        it('returns false when URL is not available', function () {
            $this->httpClient->shouldReceive('isAvailable')
                ->once()
                ->with($this->testUrl)
                ->andReturn(false);

            $source = new RemoteDictionarySource(
                $this->testUrl,
                $this->httpClient,
                $this->parser
            );

            $result = $source->isAvailable();

            expect($result)->toBeFalse();
        });
    });

    describe('getMetadata', function () {
        it('returns basic metadata before download', function () {
            $this->parser->shouldReceive('getType')
                ->once()
                ->andReturn('plain_text');

            $source = new RemoteDictionarySource(
                $this->testUrl,
                $this->httpClient,
                $this->parser
            );

            $metadata = $source->getMetadata();

            expect($metadata)
                ->toBeArray()
                ->toHaveKey('url', $this->testUrl)
                ->toHaveKey('parser_type', 'plain_text')
                ->toHaveKey('headers', [])
                ->toHaveKey('last_download', null)
                ->toHaveKey('content_length', null)
                ->toHaveKey('content_type', null);
        });

        it('includes custom headers in metadata', function () {
            $headers = ['Authorization' => 'Bearer token'];

            $this->parser->shouldReceive('getType')
                ->andReturn('plain_text');

            $source = new RemoteDictionarySource(
                $this->testUrl,
                $this->httpClient,
                $this->parser,
                $headers
            );

            $metadata = $source->getMetadata();

            expect($metadata['headers'])->toBe($headers);
        });

        it('includes download metadata after successful download', function () {
            $content = 'test content';

            $this->response->shouldReceive('isSuccessful')
                ->andReturn(true);

            $this->response->shouldReceive('getContent')
                ->andReturn($content);

            $this->response->shouldReceive('getHeader')
                ->with('Content-Type')
                ->andReturn('text/plain; charset=utf-8');

            $this->httpClient->shouldReceive('get')
                ->andReturn($this->response);

            $this->parser->shouldReceive('parse')
                ->andReturn(['test']);

            $this->parser->shouldReceive('getType')
                ->andReturn('plain_text');

            $source = new RemoteDictionarySource(
                $this->testUrl,
                $this->httpClient,
                $this->parser
            );

            // Trigger download
            $source->getWords();

            // Get metadata after download
            $metadata = $source->getMetadata();

            expect($metadata['last_download'])
                ->toBeInstanceOf(DateTimeImmutable::class);

            expect($metadata['content_length'])->toBe(strlen($content));
            expect($metadata['content_type'])->toBe('text/plain; charset=utf-8');
        });

        it('updates metadata on each download', function () {
            $content1 = 'first';
            $content2 = 'second download';

            // First download
            $this->response->shouldReceive('isSuccessful')
                ->twice()
                ->andReturn(true);

            $this->response->shouldReceive('getContent')
                ->once()
                ->andReturn($content1);

            $this->response->shouldReceive('getContent')
                ->once()
                ->andReturn($content2);

            $this->response->shouldReceive('getHeader')
                ->with('Content-Type')
                ->twice()
                ->andReturn('text/plain');

            $this->httpClient->shouldReceive('get')
                ->twice()
                ->andReturn($this->response);

            $this->parser->shouldReceive('parse')
                ->twice()
                ->andReturn(['test']);

            $this->parser->shouldReceive('getType')
                ->andReturn('plain_text');

            $source = new RemoteDictionarySource(
                $this->testUrl,
                $this->httpClient,
                $this->parser
            );

            // First download
            $source->getWords();
            $metadata1 = $source->getMetadata();

            // Small delay
            usleep(10000); // 10ms

            // Second download
            $source->getWords();
            $metadata2 = $source->getMetadata();

            // Content length should be updated
            expect($metadata2['content_length'])
                ->toBe(strlen($content2))
                ->not->toBe($metadata1['content_length']);

            // Last download time should be updated (or equal if very fast)
            expect($metadata2['last_download']->getTimestamp())
                ->toBeGreaterThanOrEqual($metadata1['last_download']->getTimestamp());
        });
    });

    describe('integration scenarios', function () {
        it('handles complete download and parse workflow', function () {
            $content = "สวัสดี\nขอบคุณ\nราตรีสวัสดิ์";
            $words = ['สวัสดี', 'ขอบคุณ', 'ราตรีสวัสดิ์'];

            $this->response->shouldReceive('isSuccessful')
                ->andReturn(true);

            $this->response->shouldReceive('getContent')
                ->andReturn($content);

            $this->response->shouldReceive('getHeader')
                ->with('Content-Type')
                ->andReturn('text/plain');

            $this->httpClient->shouldReceive('get')
                ->with($this->testUrl, [])
                ->andReturn($this->response);

            $this->httpClient->shouldReceive('isAvailable')
                ->with($this->testUrl)
                ->andReturn(true);

            $this->parser->shouldReceive('parse')
                ->with($content)
                ->andReturn($words);

            $this->parser->shouldReceive('getType')
                ->andReturn('plain_text');

            $source = new RemoteDictionarySource(
                $this->testUrl,
                $this->httpClient,
                $this->parser
            );

            expect($source->isAvailable())->toBeTrue();
            expect($source->getWords())->toBe($words);
            expect($source->getMetadata()['content_length'])->toBe(strlen($content));
        });

        it('handles URLs with various schemes', function () {
            $urls = [
                'http://example.com/dict.txt',
                'https://example.com/dict.txt',
                'https://subdomain.example.com/path/to/dict.txt',
            ];

            foreach ($urls as $url) {
                $this->response->shouldReceive('isSuccessful')
                    ->andReturn(true);

                $this->response->shouldReceive('getContent')
                    ->andReturn('test');

                $this->response->shouldReceive('getHeader')
                    ->with('Content-Type')
                    ->andReturn('text/plain');

                $this->httpClient->shouldReceive('get')
                    ->with($url, [])
                    ->andReturn($this->response);

                $this->parser->shouldReceive('parse')
                    ->andReturn(['test']);

                $source = new RemoteDictionarySource(
                    $url,
                    $this->httpClient,
                    $this->parser
                );

                expect(fn () => $source->getWords())->not->toThrow(DictionaryException::class);

                Mockery::close();
                $this->httpClient = Mockery::mock(HttpClientInterface::class);
                $this->parser = Mockery::mock(DictionaryParserInterface::class);
                $this->response = Mockery::mock(HttpResponseInterface::class);
            }
        });

        it('rejects invalid URL schemes', function () {
            $invalidUrls = [
                'javascript:alert(1)',
                'not a url at all',
                '',
            ];

            foreach ($invalidUrls as $url) {
                $source = new RemoteDictionarySource(
                    $url,
                    $this->httpClient,
                    $this->parser
                );

                expect(fn () => $source->getWords())
                    ->toThrow(DictionaryException::class);
            }
        });
    });
});
