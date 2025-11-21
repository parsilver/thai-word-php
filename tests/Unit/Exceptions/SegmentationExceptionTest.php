<?php

use Farzai\ThaiWord\Exceptions\SegmentationException;

describe('SegmentationException', function () {
    describe('instantiation', function () {
        it('can be instantiated', function () {
            $exception = new SegmentationException('Test message');

            expect($exception)->toBeInstanceOf(SegmentationException::class);
        });

        it('extends Exception', function () {
            $exception = new SegmentationException('Test message');

            expect($exception)->toBeInstanceOf(\Exception::class);
        });

        it('can be instantiated with message', function () {
            $message = 'Custom error message';
            $exception = new SegmentationException($message);

            expect($exception->getMessage())->toBe($message);
        });

        it('can be instantiated with message and code', function () {
            $message = 'Error with code';
            $code = SegmentationException::INPUT_EMPTY;
            $exception = new SegmentationException($message, $code);

            expect($exception->getMessage())->toBe($message);
            expect($exception->getCode())->toBe($code);
        });

        it('can be instantiated with previous exception', function () {
            $previous = new \Exception('Previous exception');
            $exception = new SegmentationException('Current exception', 0, $previous);

            expect($exception->getPrevious())->toBe($previous);
        });
    });

    describe('error codes', function () {
        describe('input validation codes (1000-1999)', function () {
            it('defines INPUT_EMPTY constant', function () {
                expect(SegmentationException::INPUT_EMPTY)->toBe(1001);
            });

            it('defines INPUT_INVALID_ENCODING constant', function () {
                expect(SegmentationException::INPUT_INVALID_ENCODING)->toBe(1002);
            });

            it('defines INPUT_TOO_LONG constant', function () {
                expect(SegmentationException::INPUT_TOO_LONG)->toBe(1003);
            });
        });

        describe('dictionary error codes (2000-2999)', function () {
            it('defines DICTIONARY_NOT_LOADED constant', function () {
                expect(SegmentationException::DICTIONARY_NOT_LOADED)->toBe(2001);
            });

            it('defines DICTIONARY_FILE_NOT_FOUND constant', function () {
                expect(SegmentationException::DICTIONARY_FILE_NOT_FOUND)->toBe(2002);
            });

            it('defines DICTIONARY_INVALID_FORMAT constant', function () {
                expect(SegmentationException::DICTIONARY_INVALID_FORMAT)->toBe(2003);
            });

            it('defines DICTIONARY_INVALID_SOURCE constant', function () {
                expect(SegmentationException::DICTIONARY_INVALID_SOURCE)->toBe(2004);
            });

            it('defines DICTIONARY_DOWNLOAD_FAILED constant', function () {
                expect(SegmentationException::DICTIONARY_DOWNLOAD_FAILED)->toBe(2005);
            });

            it('defines DICTIONARY_EMPTY constant', function () {
                expect(SegmentationException::DICTIONARY_EMPTY)->toBe(2006);
            });

            it('defines DICTIONARY_WRITE_FAILED constant', function () {
                expect(SegmentationException::DICTIONARY_WRITE_FAILED)->toBe(2007);
            });
        });

        describe('algorithm error codes (3000-3999)', function () {
            it('defines ALGORITHM_NOT_FOUND constant', function () {
                expect(SegmentationException::ALGORITHM_NOT_FOUND)->toBe(3001);
            });

            it('defines ALGORITHM_PROCESSING_FAILED constant', function () {
                expect(SegmentationException::ALGORITHM_PROCESSING_FAILED)->toBe(3002);
            });
        });

        describe('configuration error codes (4000-4999)', function () {
            it('defines CONFIG_INVALID constant', function () {
                expect(SegmentationException::CONFIG_INVALID)->toBe(4001);
            });

            it('defines CONFIG_MISSING_REQUIRED constant', function () {
                expect(SegmentationException::CONFIG_MISSING_REQUIRED)->toBe(4002);
            });
        });

        describe('system error codes (5000-5999)', function () {
            it('defines SYSTEM_MEMORY_LIMIT constant', function () {
                expect(SegmentationException::SYSTEM_MEMORY_LIMIT)->toBe(5001);
            });

            it('defines SYSTEM_TIME_LIMIT constant', function () {
                expect(SegmentationException::SYSTEM_TIME_LIMIT)->toBe(5002);
            });
        });
    });

    describe('throwing and catching', function () {
        it('can be thrown and caught', function () {
            expect(function () {
                throw new SegmentationException('Test exception');
            })->toThrow(SegmentationException::class);
        });

        it('can be thrown with specific error code', function () {
            try {
                throw new SegmentationException('Empty input', SegmentationException::INPUT_EMPTY);
            } catch (SegmentationException $e) {
                expect($e->getCode())->toBe(SegmentationException::INPUT_EMPTY);
                expect($e->getMessage())->toBe('Empty input');
            }
        });

        it('can be caught as Exception', function () {
            expect(function () {
                throw new SegmentationException('Test');
            })->toThrow(\Exception::class);
        });

        it('preserves exception chain', function () {
            $previous = new \RuntimeException('Previous error');
            $exception = new SegmentationException('Current error', 0, $previous);

            expect($exception->getPrevious())->toBe($previous);
            expect($exception->getPrevious()->getMessage())->toBe('Previous error');
        });
    });

    describe('error code ranges', function () {
        it('input codes are in 1000 range', function () {
            expect(SegmentationException::INPUT_EMPTY)->toBeGreaterThanOrEqual(1000);
            expect(SegmentationException::INPUT_EMPTY)->toBeLessThan(2000);
        });

        it('dictionary codes are in 2000 range', function () {
            expect(SegmentationException::DICTIONARY_NOT_LOADED)->toBeGreaterThanOrEqual(2000);
            expect(SegmentationException::DICTIONARY_NOT_LOADED)->toBeLessThan(3000);
        });

        it('algorithm codes are in 3000 range', function () {
            expect(SegmentationException::ALGORITHM_NOT_FOUND)->toBeGreaterThanOrEqual(3000);
            expect(SegmentationException::ALGORITHM_NOT_FOUND)->toBeLessThan(4000);
        });

        it('config codes are in 4000 range', function () {
            expect(SegmentationException::CONFIG_INVALID)->toBeGreaterThanOrEqual(4000);
            expect(SegmentationException::CONFIG_INVALID)->toBeLessThan(5000);
        });

        it('system codes are in 5000 range', function () {
            expect(SegmentationException::SYSTEM_MEMORY_LIMIT)->toBeGreaterThanOrEqual(5000);
            expect(SegmentationException::SYSTEM_MEMORY_LIMIT)->toBeLessThan(6000);
        });
    });
});
