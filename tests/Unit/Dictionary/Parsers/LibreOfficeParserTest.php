<?php

declare(strict_types=1);

use Farzai\ThaiWord\Dictionary\Parsers\LibreOfficeParser;
use Farzai\ThaiWord\Exceptions\DictionaryException;

describe('LibreOfficeParser', function () {
    describe('main dictionary parsing', function () {
        beforeEach(function () {
            $this->parser = new LibreOfficeParser(LibreOfficeParser::TYPE_MAIN);
        });

        it('parses main dictionary content correctly', function () {
            $content = "สวัสดี\nครับ\nผม/ph:pom\n\n  ";

            $result = $this->parser->parse($content);

            expect($result)->toBeArray()
                ->toContain('สวัสดี')
                ->toContain('ครับ')
                ->toContain('ผม'); // Should remove phonetic annotation
        });

        it('filters out invalid entries', function () {
            $content = "สวัสดี\n123456789\nครับ\n\n";

            $result = $this->parser->parse($content);

            expect($result)->toBeArray()
                ->toContain('สวัสดี')
                ->toContain('ครับ')
                ->not->toContain('123456789'); // Should filter out numbers
        });

        it('removes duplicates and sorts', function () {
            $content = "ครับ\nสวัสดี\nครับ\nสวัสดี";

            $result = $this->parser->parse($content);

            expect($result)->toBe(['ครับ', 'สวัสดี']); // Should be sorted and unique
        });

        it('throws exception for empty content', function () {
            expect(fn () => $this->parser->parse(''))
                ->toThrow(DictionaryException::class, 'Dictionary content is empty');
        });
    });

    describe('typos dictionary parsing', function () {
        beforeEach(function () {
            $this->parser = new LibreOfficeParser(LibreOfficeParser::TYPE_TYPOS_TRANSLIT);
        });

        it('parses typos content correctly', function () {
            $content = "ผลไมม์->ผลไม้\nรูปภาพ->รูปภาพ\n";

            $result = $this->parser->parse($content);

            expect($result)->toBeArray()
                ->toContain('ผลไมม์->ผลไม้')
                ->toContain('รูปภาพ->รูปภาพ');
        });

        it('handles tab-separated typos', function () {
            $content = "ผลไมม์\tผลไม้\nรูปภาพ\tรูปภาพ\n";

            $result = $this->parser->parse($content);

            expect($result)->toBeArray()
                ->toContain('ผลไมม์->ผลไม้')
                ->toContain('รูปภาพ->รูปภาพ');
        });

        it('validates typos format', function () {
            $content = "ผลไมม์->ผลไม้\ninvalid_line\nรูปภาพ->รูปภาพ\n";

            $result = $this->parser->parse($content);

            expect($result)->toBeArray()
                ->toContain('ผลไมม์->ผลไม้')
                ->toContain('รูปภาพ->รูปภาพ')
                ->not->toContain('invalid_line');
        });
    });

    describe('parser type and support detection', function () {
        it('returns correct type for main parser', function () {
            $parser = new LibreOfficeParser(LibreOfficeParser::TYPE_MAIN);
            expect($parser->getType())->toBe('libreoffice_main');
        });

        it('returns correct type for typos parser', function () {
            $parser = new LibreOfficeParser(LibreOfficeParser::TYPE_TYPOS_TRANSLIT);
            expect($parser->getType())->toBe('libreoffice_typos_translit');
        });

        it('supports valid UTF-8 content', function () {
            $parser = new LibreOfficeParser;
            expect($parser->supports('สวัสดี\nครับ'))->toBeTrue();
        });

        it('does not support empty content', function () {
            $parser = new LibreOfficeParser;
            expect($parser->supports(''))->toBeFalse();
        });

        it('does not support invalid encoding', function () {
            $parser = new LibreOfficeParser;
            expect($parser->supports("\xFF\xFE"))->toBeFalse();
        });
    });
});
