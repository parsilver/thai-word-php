<?php

use Farzai\ThaiWord\Exceptions\AlgorithmException;
use Farzai\ThaiWord\Exceptions\SegmentationException;

describe('AlgorithmException', function () {
    describe('instantiation', function () {
        it('can be instantiated', function () {
            $exception = new AlgorithmException('Test message');

            expect($exception)->toBeInstanceOf(AlgorithmException::class);
        });

        it('extends SegmentationException', function () {
            $exception = new AlgorithmException('Test message');

            expect($exception)->toBeInstanceOf(SegmentationException::class);
        });

        it('extends Exception', function () {
            $exception = new AlgorithmException('Test message');

            expect($exception)->toBeInstanceOf(\Exception::class);
        });

        it('can be instantiated with message', function () {
            $message = 'Algorithm failed';
            $exception = new AlgorithmException($message);

            expect($exception->getMessage())->toBe($message);
        });

        it('can be instantiated with message and code', function () {
            $message = 'Algorithm not found';
            $code = SegmentationException::ALGORITHM_NOT_FOUND;
            $exception = new AlgorithmException($message, $code);

            expect($exception->getMessage())->toBe($message);
            expect($exception->getCode())->toBe($code);
        });

        it('can use algorithm error codes from parent', function () {
            $exception = new AlgorithmException('Processing failed', SegmentationException::ALGORITHM_PROCESSING_FAILED);

            expect($exception->getCode())->toBe(3002);
        });
    });

    describe('inheritance', function () {
        it('inherits all error code constants from SegmentationException', function () {
            // Access parent constants through AlgorithmException
            expect(AlgorithmException::ALGORITHM_NOT_FOUND)->toBe(3001);
            expect(AlgorithmException::ALGORITHM_PROCESSING_FAILED)->toBe(3002);
        });

        it('can access dictionary error codes', function () {
            expect(AlgorithmException::DICTIONARY_NOT_LOADED)->toBe(2001);
        });

        it('can access input error codes', function () {
            expect(AlgorithmException::INPUT_EMPTY)->toBe(1001);
        });
    });

    describe('throwing and catching', function () {
        it('can be thrown and caught as AlgorithmException', function () {
            expect(function () {
                throw new AlgorithmException('Test');
            })->toThrow(AlgorithmException::class);
        });

        it('can be caught as SegmentationException', function () {
            expect(function () {
                throw new AlgorithmException('Test');
            })->toThrow(SegmentationException::class);
        });

        it('can be caught as Exception', function () {
            expect(function () {
                throw new AlgorithmException('Test');
            })->toThrow(\Exception::class);
        });

        it('preserves algorithm-specific error code', function () {
            try {
                throw new AlgorithmException('Algorithm not available', SegmentationException::ALGORITHM_NOT_FOUND);
            } catch (AlgorithmException $e) {
                expect($e->getCode())->toBe(SegmentationException::ALGORITHM_NOT_FOUND);
            }
        });
    });

    describe('use cases', function () {
        it('represents algorithm not found errors', function () {
            $exception = new AlgorithmException(
                'Longest matching algorithm not found',
                SegmentationException::ALGORITHM_NOT_FOUND
            );

            expect($exception->getMessage())->toContain('not found');
            expect($exception->getCode())->toBe(3001);
        });

        it('represents algorithm processing errors', function () {
            $exception = new AlgorithmException(
                'Failed to process text with algorithm',
                SegmentationException::ALGORITHM_PROCESSING_FAILED
            );

            expect($exception->getMessage())->toContain('process');
            expect($exception->getCode())->toBe(3002);
        });

        it('can include previous exception', function () {
            $previous = new \RuntimeException('Internal algorithm error');
            $exception = new AlgorithmException('Algorithm failed', 0, $previous);

            expect($exception->getPrevious())->toBe($previous);
        });
    });
});
