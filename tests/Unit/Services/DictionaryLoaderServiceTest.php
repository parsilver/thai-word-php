<?php

use Farzai\ThaiWord\Contracts\DictionarySourceInterface;
use Farzai\ThaiWord\Dictionary\Factories\DictionarySourceFactory;
use Farzai\ThaiWord\Exceptions\DictionaryException;
use Farzai\ThaiWord\Services\DictionaryLoaderService;

describe('DictionaryLoaderService', function () {
    beforeEach(function () {
        $this->sourceFactory = Mockery::mock(DictionarySourceFactory::class);
        $this->service = new DictionaryLoaderService($this->sourceFactory);
        $this->source = Mockery::mock(DictionarySourceInterface::class);
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('loadFromUrl', function () {
        it('can load words from URL', function () {
            $url = 'https://example.com/dictionary.txt';
            $words = ['สวัสดี', 'ขอบคุณ', 'สบายดี'];

            $this->sourceFactory->shouldReceive('create')
                ->once()
                ->with('url', $url, [
                    'timeout' => 30,
                    'parser_type' => 'main',
                ])
                ->andReturn($this->source);

            $this->source->shouldReceive('isAvailable')
                ->once()
                ->andReturn(true);

            $this->source->shouldReceive('getWords')
                ->once()
                ->andReturn($words);

            $result = $this->service->loadFromUrl($url);

            expect($result)->toBe($words);
        });

        it('can load with custom timeout', function () {
            $url = 'https://example.com/dictionary.txt';
            $timeout = 60;

            $this->sourceFactory->shouldReceive('create')
                ->once()
                ->with('url', $url, [
                    'timeout' => $timeout,
                    'parser_type' => 'main',
                ])
                ->andReturn($this->source);

            $this->source->shouldReceive('isAvailable')
                ->andReturn(true);

            $this->source->shouldReceive('getWords')
                ->andReturn([]);

            $this->service->loadFromUrl($url, $timeout);
        });

        it('can load with custom parser type', function () {
            $url = 'https://example.com/dictionary.txt';
            $parserType = 'plain';

            $this->sourceFactory->shouldReceive('create')
                ->once()
                ->with('url', $url, [
                    'timeout' => 30,
                    'parser_type' => $parserType,
                ])
                ->andReturn($this->source);

            $this->source->shouldReceive('isAvailable')
                ->andReturn(true);

            $this->source->shouldReceive('getWords')
                ->andReturn([]);

            $this->service->loadFromUrl($url, 30, $parserType);
        });

        it('throws exception when source is not available', function () {
            $url = 'https://example.com/dictionary.txt';

            $this->sourceFactory->shouldReceive('create')
                ->andReturn($this->source);

            $this->source->shouldReceive('isAvailable')
                ->once()
                ->andReturn(false);

            expect(fn () => $this->service->loadFromUrl($url))
                ->toThrow(DictionaryException::class, 'Dictionary source is not available');
        });
    });

    describe('loadFromFile', function () {
        it('can load words from file', function () {
            $filePath = '/path/to/dictionary.txt';
            $words = ['สวัสดี', 'ขอบคุณ'];

            $this->sourceFactory->shouldReceive('create')
                ->once()
                ->with('file', $filePath, [
                    'parser_type' => 'plain',
                ])
                ->andReturn($this->source);

            $this->source->shouldReceive('isAvailable')
                ->once()
                ->andReturn(true);

            $this->source->shouldReceive('getWords')
                ->once()
                ->andReturn($words);

            $result = $this->service->loadFromFile($filePath);

            expect($result)->toBe($words);
        });

        it('can load with custom parser type', function () {
            $filePath = '/path/to/dictionary.txt';
            $parserType = 'main';

            $this->sourceFactory->shouldReceive('create')
                ->once()
                ->with('file', $filePath, [
                    'parser_type' => $parserType,
                ])
                ->andReturn($this->source);

            $this->source->shouldReceive('isAvailable')
                ->andReturn(true);

            $this->source->shouldReceive('getWords')
                ->andReturn([]);

            $this->service->loadFromFile($filePath, $parserType);
        });

        it('throws exception when file is not available', function () {
            $filePath = '/non/existent/file.txt';

            $this->sourceFactory->shouldReceive('create')
                ->andReturn($this->source);

            $this->source->shouldReceive('isAvailable')
                ->once()
                ->andReturn(false);

            expect(fn () => $this->service->loadFromFile($filePath))
                ->toThrow(DictionaryException::class, 'Dictionary source is not available');
        });
    });

    describe('loadLibreOfficeThaiDictionary', function () {
        it('can load LibreOffice main dictionary', function () {
            $words = ['สวัสดี', 'ขอบคุณ'];

            $this->sourceFactory->shouldReceive('create')
                ->once()
                ->andReturn($this->source);

            $this->source->shouldReceive('isAvailable')
                ->once()
                ->andReturn(true);

            $this->source->shouldReceive('getWords')
                ->once()
                ->andReturn($words);

            $result = $this->service->loadLibreOfficeThaiDictionary();

            expect($result)->toBe($words);
        });

        it('can load different dictionary types', function () {
            $types = ['main', 'typos_translit', 'typos_common'];

            foreach ($types as $type) {
                $this->sourceFactory->shouldReceive('create')
                    ->once()
                    ->andReturn($this->source);

                $this->source->shouldReceive('isAvailable')
                    ->andReturn(true);

                $this->source->shouldReceive('getWords')
                    ->andReturn([]);

                $result = $this->service->loadLibreOfficeThaiDictionary($type);

                expect($result)->toBeArray();

                Mockery::close();
                $this->sourceFactory = Mockery::mock(DictionarySourceFactory::class);
                $this->service = new DictionaryLoaderService($this->sourceFactory);
                $this->source = Mockery::mock(DictionarySourceInterface::class);
            }
        });

        it('can load with custom timeout', function () {
            $timeout = 60;

            $this->sourceFactory->shouldReceive('create')
                ->once()
                ->andReturn($this->source);

            $this->source->shouldReceive('isAvailable')
                ->andReturn(true);

            $this->source->shouldReceive('getWords')
                ->andReturn([]);

            $this->service->loadLibreOfficeThaiDictionary('main', $timeout);
        });
    });

    describe('loadFromDictionarySource', function () {
        it('can load words from available source', function () {
            $words = ['สวัสดี', 'ขอบคุณ', 'สบายดี'];

            $this->source->shouldReceive('isAvailable')
                ->once()
                ->andReturn(true);

            $this->source->shouldReceive('getWords')
                ->once()
                ->andReturn($words);

            $result = $this->service->loadFromDictionarySource($this->source);

            expect($result)->toBe($words);
        });

        it('throws exception when source is not available', function () {
            $this->source->shouldReceive('isAvailable')
                ->once()
                ->andReturn(false);

            expect(fn () => $this->service->loadFromDictionarySource($this->source))
                ->toThrow(DictionaryException::class, 'Dictionary source is not available');
        });

        it('propagates exceptions from getWords', function () {
            $this->source->shouldReceive('isAvailable')
                ->once()
                ->andReturn(true);

            $this->source->shouldReceive('getWords')
                ->once()
                ->andThrow(new DictionaryException('Parse error'));

            expect(fn () => $this->service->loadFromDictionarySource($this->source))
                ->toThrow(DictionaryException::class, 'Parse error');
        });
    });

    describe('isUrl', function () {
        it('returns true for valid HTTP URLs', function () {
            $validUrls = [
                'http://example.com',
                'https://example.com',
                'http://example.com/path',
                'https://example.com/path/to/file.txt',
                'https://subdomain.example.com',
                'http://example.com:8080',
                'https://example.com/path?query=value',
            ];

            foreach ($validUrls as $url) {
                expect($this->service->isUrl($url))->toBeTrue();
            }
        });

        it('returns false for non-URLs', function () {
            $nonUrls = [
                '/path/to/file.txt',
                'relative/path.txt',
                'file.txt',
                'not a url',
                '',
            ];

            foreach ($nonUrls as $nonUrl) {
                expect($this->service->isUrl($nonUrl))->toBeFalse();
            }
        });

        it('returns false for invalid URL formats', function () {
            $invalidUrls = [
                'javascript:alert(1)',
                'data:text/plain,hello',
                '//example.com',
                'example.com',
            ];

            foreach ($invalidUrls as $url) {
                expect($this->service->isUrl($url))->toBeFalse();
            }
        });
    });

    describe('load', function () {
        it('detects and loads from URL', function () {
            $url = 'https://example.com/dictionary.txt';
            $words = ['สวัสดี'];

            $this->sourceFactory->shouldReceive('create')
                ->once()
                ->with('url', $url, Mockery::any())
                ->andReturn($this->source);

            $this->source->shouldReceive('isAvailable')
                ->andReturn(true);

            $this->source->shouldReceive('getWords')
                ->andReturn($words);

            $result = $this->service->load($url);

            expect($result)->toBe($words);
        });

        it('detects and loads from file path', function () {
            $filePath = '/path/to/dictionary.txt';
            $words = ['สวัสดี'];

            $this->sourceFactory->shouldReceive('create')
                ->once()
                ->with('file', $filePath, Mockery::any())
                ->andReturn($this->source);

            $this->source->shouldReceive('isAvailable')
                ->andReturn(true);

            $this->source->shouldReceive('getWords')
                ->andReturn($words);

            $result = $this->service->load($filePath);

            expect($result)->toBe($words);
        });

        it('passes timeout to URL loads', function () {
            $url = 'https://example.com/dictionary.txt';
            $timeout = 60;

            $this->sourceFactory->shouldReceive('create')
                ->once()
                ->with('url', $url, [
                    'timeout' => $timeout,
                    'parser_type' => 'plain',
                ])
                ->andReturn($this->source);

            $this->source->shouldReceive('isAvailable')
                ->andReturn(true);

            $this->source->shouldReceive('getWords')
                ->andReturn([]);

            $this->service->load($url, $timeout);
        });

        it('passes parser type correctly', function () {
            $filePath = '/path/to/dictionary.txt';
            $parserType = 'main';

            $this->sourceFactory->shouldReceive('create')
                ->once()
                ->with('file', $filePath, [
                    'parser_type' => $parserType,
                ])
                ->andReturn($this->source);

            $this->source->shouldReceive('isAvailable')
                ->andReturn(true);

            $this->source->shouldReceive('getWords')
                ->andReturn([]);

            $this->service->load($filePath, 30, $parserType);
        });

        it('handles relative file paths', function () {
            $relativePath = 'dictionary.txt';

            $this->sourceFactory->shouldReceive('create')
                ->once()
                ->with('file', $relativePath, Mockery::any())
                ->andReturn($this->source);

            $this->source->shouldReceive('isAvailable')
                ->andReturn(true);

            $this->source->shouldReceive('getWords')
                ->andReturn([]);

            $this->service->load($relativePath);
        });

        it('handles absolute file paths', function () {
            $absolutePath = '/absolute/path/to/dictionary.txt';

            $this->sourceFactory->shouldReceive('create')
                ->once()
                ->with('file', $absolutePath, Mockery::any())
                ->andReturn($this->source);

            $this->source->shouldReceive('isAvailable')
                ->andReturn(true);

            $this->source->shouldReceive('getWords')
                ->andReturn([]);

            $this->service->load($absolutePath);
        });
    });

    describe('integration scenarios', function () {
        it('can handle multiple loads from same service instance', function () {
            $url = 'https://example.com/dict1.txt';
            $file = '/path/to/dict2.txt';

            $source1 = Mockery::mock(DictionarySourceInterface::class);
            $source2 = Mockery::mock(DictionarySourceInterface::class);

            // First load from URL
            $this->sourceFactory->shouldReceive('create')
                ->once()
                ->with('url', $url, Mockery::any())
                ->andReturn($source1);

            $source1->shouldReceive('isAvailable')
                ->andReturn(true);

            $source1->shouldReceive('getWords')
                ->andReturn(['word1']);

            // Second load from file
            $this->sourceFactory->shouldReceive('create')
                ->once()
                ->with('file', $file, Mockery::any())
                ->andReturn($source2);

            $source2->shouldReceive('isAvailable')
                ->andReturn(true);

            $source2->shouldReceive('getWords')
                ->andReturn(['word2']);

            $result1 = $this->service->load($url);
            $result2 = $this->service->load($file);

            expect($result1)->toBe(['word1']);
            expect($result2)->toBe(['word2']);
        });

        it('validates source availability before loading', function () {
            $url = 'https://example.com/dictionary.txt';

            $this->sourceFactory->shouldReceive('create')
                ->andReturn($this->source);

            $this->source->shouldReceive('isAvailable')
                ->once()
                ->andReturn(false);

            // Should not call getWords() if not available
            $this->source->shouldReceive('getWords')
                ->never();

            expect(fn () => $this->service->load($url))
                ->toThrow(DictionaryException::class);
        });

        it('handles empty word arrays from sources', function () {
            $url = 'https://example.com/empty.txt';

            $this->sourceFactory->shouldReceive('create')
                ->andReturn($this->source);

            $this->source->shouldReceive('isAvailable')
                ->andReturn(true);

            $this->source->shouldReceive('getWords')
                ->andReturn([]);

            $result = $this->service->load($url);

            expect($result)->toBeArray()->toBeEmpty();
        });
    });

    describe('error handling', function () {
        it('propagates factory exceptions', function () {
            $url = 'https://example.com/dictionary.txt';

            $this->sourceFactory->shouldReceive('create')
                ->once()
                ->andThrow(new \InvalidArgumentException('Invalid configuration'));

            expect(fn () => $this->service->loadFromUrl($url))
                ->toThrow(\InvalidArgumentException::class, 'Invalid configuration');
        });

        it('handles source creation failures gracefully', function () {
            $url = 'https://example.com/dictionary.txt';

            $this->sourceFactory->shouldReceive('create')
                ->once()
                ->andThrow(new \RuntimeException('HTTP client not available'));

            expect(fn () => $this->service->loadFromUrl($url))
                ->toThrow(\RuntimeException::class);
        });
    });
});
