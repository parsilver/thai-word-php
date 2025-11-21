<?php

use Farzai\ThaiWord\Contracts\DictionaryParserInterface;
use Farzai\ThaiWord\Dictionary\Sources\RemoteDictionarySource;
use Farzai\ThaiWord\Exceptions\DictionaryException;
use Farzai\ThaiWord\Exceptions\SegmentationException;
use Farzai\Transport\Exceptions\ClientException;
use Farzai\Transport\Exceptions\NetworkException;
use Farzai\Transport\Exceptions\RetryExhaustedException;
use Farzai\Transport\Exceptions\ServerException;
use Farzai\Transport\Exceptions\TimeoutException;
use Farzai\Transport\Retry\RetryContext;
use Farzai\Transport\Transport;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

describe('RemoteDictionarySource', function () {
    beforeEach(function () {
        $this->transport = Mockery::mock(Transport::class);
        $this->parser = Mockery::mock(DictionaryParserInterface::class);
        $this->response = Mockery::mock(ResponseInterface::class);
        $this->stream = Mockery::mock(StreamInterface::class);
        $this->testUrl = 'https://example.com/dictionary.txt';
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('getWords', function () {
        it('can download and parse words from remote URL', function () {
            $content = "สวัสดี\nขอบคุณ\nสบายดี";
            $words = ['สวัสดี', 'ขอบคุณ', 'สบายดี'];

            $this->response->shouldReceive('getStatusCode')
                ->once()
                ->andReturn(200);

            $this->response->shouldReceive('getBody')
                ->once()
                ->andReturn($this->stream);

            $this->stream->shouldReceive('__toString')
                ->once()
                ->andReturn($content);

            $this->response->shouldReceive('getHeaderLine')
                ->with('Content-Type')
                ->andReturn('text/plain; charset=utf-8');

            $this->transport->shouldReceive('sendRequest')
                ->once()
                ->with(Mockery::on(function ($request) {
                    return $request->getMethod() === 'GET' &&
                           (string) $request->getUri() === $this->testUrl;
                }))
                ->andReturn($this->response);

            $this->parser->shouldReceive('parse')
                ->once()
                ->with($content)
                ->andReturn($words);

            $source = new RemoteDictionarySource(
                $this->testUrl,
                $this->transport,
                $this->parser
            );

            $result = $source->getWords();

            expect($result)->toBe($words);
        });

        it('throws exception for invalid URL', function () {
            $invalidUrl = 'not-a-valid-url';

            $source = new RemoteDictionarySource(
                $invalidUrl,
                $this->transport,
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
            $this->response->shouldReceive('getStatusCode')
                ->once()
                ->andReturn(404);

            $this->transport->shouldReceive('sendRequest')
                ->once()
                ->andReturn($this->response);

            $source = new RemoteDictionarySource(
                $this->testUrl,
                $this->transport,
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
            $this->response->shouldReceive('getStatusCode')
                ->once()
                ->andReturn(200);

            $this->response->shouldReceive('getBody')
                ->once()
                ->andReturn($this->stream);

            $this->stream->shouldReceive('__toString')
                ->once()
                ->andReturn('');

            $this->transport->shouldReceive('sendRequest')
                ->once()
                ->andReturn($this->response);

            $source = new RemoteDictionarySource(
                $this->testUrl,
                $this->transport,
                $this->parser
            );

            expect(fn () => $source->getWords())
                ->toThrow(
                    DictionaryException::class,
                    "Empty response received from: {$this->testUrl}",
                    SegmentationException::DICTIONARY_EMPTY
                );
        });

        it('wraps Transport exceptions in DictionaryException', function () {
            $networkException = new RuntimeException('Network error');

            $this->transport->shouldReceive('sendRequest')
                ->once()
                ->andThrow($networkException);

            $source = new RemoteDictionarySource(
                $this->testUrl,
                $this->transport,
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
                $this->response->shouldReceive('getStatusCode')
                    ->once()
                    ->andReturn($code);

                $this->transport->shouldReceive('sendRequest')
                    ->once()
                    ->andReturn($this->response);

                $source = new RemoteDictionarySource(
                    $this->testUrl,
                    $this->transport,
                    $this->parser
                );

                expect(fn () => $source->getWords())
                    ->toThrow(DictionaryException::class);

                Mockery::close();
                $this->transport = Mockery::mock(Transport::class);
                $this->parser = Mockery::mock(DictionaryParserInterface::class);
                $this->response = Mockery::mock(ResponseInterface::class);
                $this->stream = Mockery::mock(StreamInterface::class);
            }
        });
    });

    describe('Transport exception handling', function () {
        it('wraps ClientException (4xx errors) in DictionaryException', function () {
            $mockRequest = Mockery::mock(RequestInterface::class);
            $mockResponse = Mockery::mock(ResponseInterface::class);
            $mockResponse->shouldReceive('getStatusCode')->andReturn(400);

            $clientException = new ClientException('Bad Request', $mockRequest, $mockResponse);

            $this->transport->shouldReceive('sendRequest')
                ->once()
                ->andThrow($clientException);

            $source = new RemoteDictionarySource(
                $this->testUrl,
                $this->transport,
                $this->parser
            );

            try {
                $source->getWords();
                expect(false)->toBeTrue(); // Should not reach here
            } catch (DictionaryException $e) {
                expect($e->getMessage())
                    ->toContain('Client error')
                    ->toContain('HTTP 400')
                    ->toContain($this->testUrl);
                expect($e->getCode())->toBe(SegmentationException::DICTIONARY_DOWNLOAD_FAILED);
                expect($e->getPrevious())->toBe($clientException);
            }
        });

        it('wraps ServerException (5xx errors) in DictionaryException', function () {
            $mockRequest = Mockery::mock(RequestInterface::class);
            $mockResponse = Mockery::mock(ResponseInterface::class);
            $mockResponse->shouldReceive('getStatusCode')->andReturn(500);

            $serverException = new ServerException('Internal Server Error', $mockRequest, $mockResponse);

            $this->transport->shouldReceive('sendRequest')
                ->once()
                ->andThrow($serverException);

            $source = new RemoteDictionarySource(
                $this->testUrl,
                $this->transport,
                $this->parser
            );

            try {
                $source->getWords();
                expect(false)->toBeTrue(); // Should not reach here
            } catch (DictionaryException $e) {
                expect($e->getMessage())
                    ->toContain('Server error')
                    ->toContain('HTTP 500')
                    ->toContain($this->testUrl);
                expect($e->getCode())->toBe(SegmentationException::DICTIONARY_DOWNLOAD_FAILED);
                expect($e->getPrevious())->toBe($serverException);
            }
        });

        it('wraps NetworkException in DictionaryException', function () {
            $mockRequest = Mockery::mock(RequestInterface::class);
            $networkException = new NetworkException('Connection refused', $mockRequest);

            $this->transport->shouldReceive('sendRequest')
                ->once()
                ->andThrow($networkException);

            $source = new RemoteDictionarySource(
                $this->testUrl,
                $this->transport,
                $this->parser
            );

            try {
                $source->getWords();
                expect(false)->toBeTrue(); // Should not reach here
            } catch (DictionaryException $e) {
                expect($e->getMessage())
                    ->toContain('Network error')
                    ->toContain($this->testUrl)
                    ->toContain('Connection refused');
                expect($e->getCode())->toBe(SegmentationException::DICTIONARY_DOWNLOAD_FAILED);
                expect($e->getPrevious())->toBe($networkException);
            }
        });

        it('wraps TimeoutException in DictionaryException', function () {
            $mockRequest = Mockery::mock(RequestInterface::class);
            $timeoutException = new TimeoutException('Request timed out after 30 seconds', $mockRequest, 30);

            $this->transport->shouldReceive('sendRequest')
                ->once()
                ->andThrow($timeoutException);

            $source = new RemoteDictionarySource(
                $this->testUrl,
                $this->transport,
                $this->parser
            );

            try {
                $source->getWords();
                expect(false)->toBeTrue(); // Should not reach here
            } catch (DictionaryException $e) {
                expect($e->getMessage())
                    ->toContain('Timeout')
                    ->toContain($this->testUrl)
                    ->toContain('Request timed out');
                expect($e->getCode())->toBe(SegmentationException::DICTIONARY_DOWNLOAD_FAILED);
                expect($e->getPrevious())->toBe($timeoutException);
            }
        });

        it('wraps RetryExhaustedException in DictionaryException', function () {
            // Mock RetryExhaustedException - can't easily create one due to readonly properties
            // so we'll use a mock that simulates the exception being thrown
            $retryException = Mockery::mock(RetryExhaustedException::class);
            $retryException->shouldReceive('getMessage')->andReturn('Failed after retries');
            $retryException->shouldReceive('getAttempts')->andReturn(3);

            $this->transport->shouldReceive('sendRequest')
                ->once()
                ->andThrow($retryException);

            $source = new RemoteDictionarySource(
                $this->testUrl,
                $this->transport,
                $this->parser
            );

            try {
                $source->getWords();
                expect(false)->toBeTrue(); // Should not reach here
            } catch (DictionaryException $e) {
                expect($e->getMessage())
                    ->toContain('Download failed after')
                    ->toContain('attempts')
                    ->toContain($this->testUrl);
                expect($e->getCode())->toBe(SegmentationException::DICTIONARY_DOWNLOAD_FAILED);
                expect($e->getPrevious())->toBe($retryException);
            }
        });
    });

    describe('isAvailable', function () {
        it('returns true when URL is accessible', function () {
            $this->response->shouldReceive('getStatusCode')
                ->once()
                ->andReturn(200);

            $this->transport->shouldReceive('sendRequest')
                ->once()
                ->with(Mockery::on(function ($request) {
                    return $request->getMethod() === 'HEAD' &&
                           (string) $request->getUri() === $this->testUrl;
                }))
                ->andReturn($this->response);

            $source = new RemoteDictionarySource(
                $this->testUrl,
                $this->transport,
                $this->parser
            );

            $result = $source->isAvailable();

            expect($result)->toBeTrue();
        });

        it('returns false when URL is not accessible', function () {
            $this->response->shouldReceive('getStatusCode')
                ->once()
                ->andReturn(404);

            $this->transport->shouldReceive('sendRequest')
                ->once()
                ->andReturn($this->response);

            $source = new RemoteDictionarySource(
                $this->testUrl,
                $this->transport,
                $this->parser
            );

            $result = $source->isAvailable();

            expect($result)->toBeFalse();
        });

        it('returns false when transport throws exception', function () {
            $this->transport->shouldReceive('sendRequest')
                ->once()
                ->andThrow(new RuntimeException('Network error'));

            $source = new RemoteDictionarySource(
                $this->testUrl,
                $this->transport,
                $this->parser
            );

            $result = $source->isAvailable();

            expect($result)->toBeFalse();
        });

        it('returns false for invalid URLs', function () {
            $invalidUrl = 'not-a-valid-url';

            $source = new RemoteDictionarySource(
                $invalidUrl,
                $this->transport,
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
                $this->transport,
                $this->parser
            );

            $metadata = $source->getMetadata();

            expect($metadata)
                ->toBeArray()
                ->toHaveKey('url', $this->testUrl)
                ->toHaveKey('parser_type', 'plain_text')
                ->toHaveKey('last_download', null)
                ->toHaveKey('content_length', null)
                ->toHaveKey('content_type', null);
        });

        it('includes download metadata after successful download', function () {
            $content = 'test content';

            $this->response->shouldReceive('getStatusCode')
                ->andReturn(200);

            $this->response->shouldReceive('getBody')
                ->andReturn($this->stream);

            $this->stream->shouldReceive('__toString')
                ->andReturn($content);

            $this->response->shouldReceive('getHeaderLine')
                ->with('Content-Type')
                ->andReturn('text/plain; charset=utf-8');

            $this->transport->shouldReceive('sendRequest')
                ->andReturn($this->response);

            $this->parser->shouldReceive('parse')
                ->andReturn(['test']);

            $this->parser->shouldReceive('getType')
                ->andReturn('plain_text');

            $source = new RemoteDictionarySource(
                $this->testUrl,
                $this->transport,
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
    });

    describe('integration scenarios', function () {
        it('handles complete download and parse workflow', function () {
            $content = "สวัสดี\nขอบคุณ\nราตรีสวัสดิ์";
            $words = ['สวัสดี', 'ขอบคุณ', 'ราตรีสวัสดิ์'];

            // For isAvailable (HEAD request)
            $headResponse = Mockery::mock(ResponseInterface::class);
            $headResponse->shouldReceive('getStatusCode')->andReturn(200);

            // For getWords (GET request)
            $getResponse = Mockery::mock(ResponseInterface::class);
            $getResponse->shouldReceive('getStatusCode')->andReturn(200);
            $getResponse->shouldReceive('getBody')->andReturn($this->stream);
            $getResponse->shouldReceive('getHeaderLine')->with('Content-Type')->andReturn('text/plain');

            $this->stream->shouldReceive('__toString')->andReturn($content);

            $this->transport->shouldReceive('sendRequest')
                ->with(Mockery::on(fn ($r) => $r->getMethod() === 'HEAD'))
                ->andReturn($headResponse);

            $this->transport->shouldReceive('sendRequest')
                ->with(Mockery::on(fn ($r) => $r->getMethod() === 'GET'))
                ->andReturn($getResponse);

            $this->parser->shouldReceive('parse')
                ->with($content)
                ->andReturn($words);

            $this->parser->shouldReceive('getType')
                ->andReturn('plain_text');

            $source = new RemoteDictionarySource(
                $this->testUrl,
                $this->transport,
                $this->parser
            );

            expect($source->isAvailable())->toBeTrue();
            expect($source->getWords())->toBe($words);
            expect($source->getMetadata()['content_length'])->toBe(strlen($content));
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
                    $this->transport,
                    $this->parser
                );

                expect(fn () => $source->getWords())
                    ->toThrow(DictionaryException::class);
            }
        });
    });
});
