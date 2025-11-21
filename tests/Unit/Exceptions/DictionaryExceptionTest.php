<?php

use Farzai\ThaiWord\Exceptions\DictionaryException;
use Farzai\ThaiWord\Exceptions\SegmentationException;

describe('DictionaryException', function () {
    describe('instantiation', function () {
        it('can be instantiated', function () {
            $exception = new DictionaryException('Test message');

            expect($exception)->toBeInstanceOf(DictionaryException::class);
        });

        it('extends SegmentationException', function () {
            $exception = new DictionaryException('Test message');

            expect($exception)->toBeInstanceOf(SegmentationException::class);
        });

        it('extends Exception', function () {
            $exception = new DictionaryException('Test message');

            expect($exception)->toBeInstanceOf(\Exception::class);
        });

        it('can be instantiated with message', function () {
            $message = 'Dictionary not found';
            $exception = new DictionaryException($message);

            expect($exception->getMessage())->toBe($message);
        });

        it('can be instantiated with message and code', function () {
            $message = 'Dictionary file missing';
            $code = SegmentationException::DICTIONARY_FILE_NOT_FOUND;
            $exception = new DictionaryException($message, $code);

            expect($exception->getMessage())->toBe($message);
            expect($exception->getCode())->toBe($code);
        });

        it('can use dictionary error codes from parent', function () {
            $exception = new DictionaryException('Dictionary not loaded', SegmentationException::DICTIONARY_NOT_LOADED);

            expect($exception->getCode())->toBe(2001);
        });
    });

    describe('inheritance', function () {
        it('inherits all error code constants from SegmentationException', function () {
            // Access parent constants through DictionaryException
            expect(DictionaryException::DICTIONARY_NOT_LOADED)->toBe(2001);
            expect(DictionaryException::DICTIONARY_FILE_NOT_FOUND)->toBe(2002);
            expect(DictionaryException::DICTIONARY_INVALID_FORMAT)->toBe(2003);
            expect(DictionaryException::DICTIONARY_INVALID_SOURCE)->toBe(2004);
            expect(DictionaryException::DICTIONARY_DOWNLOAD_FAILED)->toBe(2005);
            expect(DictionaryException::DICTIONARY_EMPTY)->toBe(2006);
            expect(DictionaryException::DICTIONARY_WRITE_FAILED)->toBe(2007);
        });

        it('can access algorithm error codes', function () {
            expect(DictionaryException::ALGORITHM_NOT_FOUND)->toBe(3001);
        });

        it('can access input error codes', function () {
            expect(DictionaryException::INPUT_EMPTY)->toBe(1001);
        });
    });

    describe('throwing and catching', function () {
        it('can be thrown and caught as DictionaryException', function () {
            expect(function () {
                throw new DictionaryException('Test');
            })->toThrow(DictionaryException::class);
        });

        it('can be caught as SegmentationException', function () {
            expect(function () {
                throw new DictionaryException('Test');
            })->toThrow(SegmentationException::class);
        });

        it('can be caught as Exception', function () {
            expect(function () {
                throw new DictionaryException('Test');
            })->toThrow(\Exception::class);
        });

        it('preserves dictionary-specific error code', function () {
            try {
                throw new DictionaryException('Dictionary not loaded', SegmentationException::DICTIONARY_NOT_LOADED);
            } catch (DictionaryException $e) {
                expect($e->getCode())->toBe(SegmentationException::DICTIONARY_NOT_LOADED);
            }
        });
    });

    describe('use cases', function () {
        it('represents dictionary not loaded errors', function () {
            $exception = new DictionaryException(
                'No dictionary has been loaded',
                SegmentationException::DICTIONARY_NOT_LOADED
            );

            expect($exception->getMessage())->toContain('loaded');
            expect($exception->getCode())->toBe(2001);
        });

        it('represents dictionary file not found errors', function () {
            $exception = new DictionaryException(
                'Dictionary file does not exist: /path/to/dict.txt',
                SegmentationException::DICTIONARY_FILE_NOT_FOUND
            );

            expect($exception->getMessage())->toContain('not exist');
            expect($exception->getCode())->toBe(2002);
        });

        it('represents dictionary invalid format errors', function () {
            $exception = new DictionaryException(
                'Dictionary file has invalid format',
                SegmentationException::DICTIONARY_INVALID_FORMAT
            );

            expect($exception->getMessage())->toContain('invalid format');
            expect($exception->getCode())->toBe(2003);
        });

        it('represents dictionary download failed errors', function () {
            $exception = new DictionaryException(
                'Failed to download dictionary from remote source',
                SegmentationException::DICTIONARY_DOWNLOAD_FAILED
            );

            expect($exception->getMessage())->toContain('download');
            expect($exception->getCode())->toBe(2005);
        });

        it('represents dictionary write failed errors', function () {
            $exception = new DictionaryException(
                'Failed to write dictionary to file',
                SegmentationException::DICTIONARY_WRITE_FAILED
            );

            expect($exception->getMessage())->toContain('write');
            expect($exception->getCode())->toBe(2007);
        });

        it('can include previous exception', function () {
            $previous = new \RuntimeException('File system error');
            $exception = new DictionaryException('Dictionary operation failed', 0, $previous);

            expect($exception->getPrevious())->toBe($previous);
        });
    });

    describe('dictionary error code validation', function () {
        it('all dictionary codes are in 2000 range', function () {
            $codes = [
                DictionaryException::DICTIONARY_NOT_LOADED,
                DictionaryException::DICTIONARY_FILE_NOT_FOUND,
                DictionaryException::DICTIONARY_INVALID_FORMAT,
                DictionaryException::DICTIONARY_INVALID_SOURCE,
                DictionaryException::DICTIONARY_DOWNLOAD_FAILED,
                DictionaryException::DICTIONARY_EMPTY,
                DictionaryException::DICTIONARY_WRITE_FAILED,
            ];

            foreach ($codes as $code) {
                expect($code)->toBeGreaterThanOrEqual(2000);
                expect($code)->toBeLessThan(3000);
            }
        });
    });
});
