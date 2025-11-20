<?php

use Farzai\ThaiWord\Dictionary\Parsers\PlainTextParser;
use Farzai\ThaiWord\Exceptions\DictionaryException;
use Farzai\ThaiWord\Exceptions\SegmentationException;

describe('PlainTextParser', function () {
    describe('parse', function () {
        it('can parse simple line-based dictionary', function () {
            $parser = new PlainTextParser;
            $content = "สวัสดี\nขอบคุณ\nสบายดี";

            $words = $parser->parse($content);

            expect($words)
                ->toBeArray()
                ->toHaveCount(3)
                ->toContain('สวัสดี', 'ขอบคุณ', 'สบายดี');
        });

        it('throws exception for empty content', function () {
            $parser = new PlainTextParser;

            expect(fn () => $parser->parse(''))
                ->toThrow(
                    DictionaryException::class,
                    'Dictionary content is empty',
                    SegmentationException::DICTIONARY_EMPTY
                );
        });

        it('skips empty lines', function () {
            $parser = new PlainTextParser;
            $content = "สวัสดี\n\n\nขอบคุณ\n\nสบายดี";

            $words = $parser->parse($content);

            expect($words)->toHaveCount(3);
        });

        it('skips comment lines starting with #', function () {
            $parser = new PlainTextParser;
            $content = "สวัสดี\n# This is a comment\nขอบคุณ\n#Another comment\nสบายดี";

            $words = $parser->parse($content);

            expect($words)
                ->toHaveCount(3)
                ->not->toContain('# This is a comment');
        });

        it('skips comment lines starting with //', function () {
            $parser = new PlainTextParser;
            $content = "สวัสดี\n// Comment\nขอบคุณ\n// Another\nสบายดี";

            $words = $parser->parse($content);

            expect($words)
                ->toHaveCount(3)
                ->not->toContain('// Comment');
        });

        it('trims whitespace from words', function () {
            $parser = new PlainTextParser;
            $content = "  สวัสดี  \n\tขอบคุณ\t\n สบายดี ";

            $words = $parser->parse($content);

            expect($words)->toEqual(['ขอบคุณ', 'สบายดี', 'สวัสดี']);
        });

        it('removes duplicate words', function () {
            $parser = new PlainTextParser;
            $content = "สวัสดี\nขอบคุณ\nสวัสดี\nขอบคุณ\nสบายดี";

            $words = $parser->parse($content);

            expect($words)->toHaveCount(3);
        });

        it('sorts words alphabetically', function () {
            $parser = new PlainTextParser;
            $content = "สบายดี\nสวัสดี\nขอบคุณ";

            $words = $parser->parse($content);

            expect($words)->toEqual(['ขอบคุณ', 'สบายดี', 'สวัสดี']);
        });

        it('handles different line endings (LF, CRLF, CR)', function () {
            $parser = new PlainTextParser;

            // Test LF (\n)
            $words1 = $parser->parse("สวัสดี\nขอบคุณ");
            expect($words1)->toHaveCount(2);

            // Test CRLF (\r\n)
            $words2 = $parser->parse("สวัสดี\r\nขอบคุณ");
            expect($words2)->toHaveCount(2);

            // Test CR (\r)
            $words3 = $parser->parse("สวัสดี\rขอบคุณ");
            expect($words3)->toHaveCount(2);
        });

        it('validates Thai characters by default', function () {
            $parser = new PlainTextParser;
            $content = "สวัสดี\nHello\nขอบคุณ\n12345";

            $words = $parser->parse($content);

            expect($words)
                ->not->toContain('Hello')
                ->not->toContain('12345')
                ->toContain('สวัสดี', 'ขอบคุณ');
        });

        it('can disable Thai validation', function () {
            $parser = new PlainTextParser(validateThai: false);
            $content = "สวัสดี\nHello\nWorld";

            $words = $parser->parse($content);

            expect($words)->toContain('Hello', 'World', 'สวัสดี');
        });

        it('respects minimum length constraint', function () {
            $parser = new PlainTextParser(minLength: 3);
            $content = "สวัสดี\nดี\nขอบคุณมาก";

            $words = $parser->parse($content);

            expect($words)
                ->toContain('สวัสดี', 'ขอบคุณมาก')
                ->not->toContain('ดี'); // Too short
        });

        it('respects maximum length constraint', function () {
            $parser = new PlainTextParser(maxLength: 5);
            $content = "สวัสดี\nดีมาก\nขอบคุณมากครับ";

            $words = $parser->parse($content);

            // สวัสดี is 6 chars (too long), ดีมาก is 5 chars, ขอบคุณมากครับ is 12 chars (too long)
            expect($words)
                ->toContain('ดีมาก') // 5 chars - should be included
                ->not->toContain('สวัสดี') // Too long (6 chars)
                ->not->toContain('ขอบคุณมากครับ'); // Too long
        });

        it('rejects words with invalid UTF-8 encoding', function () {
            $parser = new PlainTextParser(validateThai: false);
            $content = "สวัสดี\n\xFF\xFE\nขอบคุณ";

            $words = $parser->parse($content);

            expect($words)
                ->toContain('สวัสดี', 'ขอบคุณ')
                ->toHaveCount(2);
        });

        it('rejects words with excessive numbers', function () {
            $parser = new PlainTextParser;
            $content = "สวัสดี\nสวัสดี12\nสวัสดี1234";

            $words = $parser->parse($content);

            expect($words)
                ->toContain('สวัสดี', 'สวัสดี12')
                ->not->toContain('สวัสดี1234'); // Has 4+ consecutive numbers (3+ is rejected)
        });

        it('throws exception when preg_split fails', function () {
            // This is hard to trigger, but we can document the behavior
            // preg_split would return false on error, triggering exception
        })->skip('Hard to trigger preg_split failure in normal conditions');

        it('handles very long content efficiently', function () {
            $parser = new PlainTextParser;
            $lines = array_fill(0, 1000, 'สวัสดี');
            $content = implode("\n", $lines);

            $words = $parser->parse($content);

            // Should deduplicate to just one word
            expect($words)->toHaveCount(1);
        });
    });

    describe('supports', function () {
        it('returns true for valid plain text with Thai words', function () {
            $parser = new PlainTextParser;
            $content = "สวัสดี\nขอบคุณ\nสบายดี";

            expect($parser->supports($content))->toBeTrue();
        });

        it('returns false for empty content', function () {
            $parser = new PlainTextParser;

            expect($parser->supports(''))->toBeFalse();
        });

        it('returns false for invalid UTF-8', function () {
            $parser = new PlainTextParser;
            $content = "\xFF\xFE\x00\x00";

            expect($parser->supports($content))->toBeFalse();
        });

        it('checks only first 10 lines for performance', function () {
            $parser = new PlainTextParser;
            // First 10 lines have Thai, rest doesn't
            $validLines = array_fill(0, 10, 'สวัสดี');
            $invalidLines = array_fill(0, 100, 'invalid');
            $content = implode("\n", array_merge($validLines, $invalidLines));

            expect($parser->supports($content))->toBeTrue();
        });

        it('returns true when at least one valid line exists', function () {
            $parser = new PlainTextParser;
            $content = "# Comment\n\nสวัสดี\n# Another comment";

            expect($parser->supports($content))->toBeTrue();
        });

        it('returns false when no valid lines exist', function () {
            $parser = new PlainTextParser;
            $content = "# Only comments\n# And empty lines\n\n";

            expect($parser->supports($content))->toBeFalse();
        });

        it('skips empty lines and comments when checking', function () {
            $parser = new PlainTextParser;
            $content = "\n\n# Comment\n\n// Another\n\nสวัสดี";

            expect($parser->supports($content))->toBeTrue();
        });

        it('returns false for content without Thai when validation enabled', function () {
            $parser = new PlainTextParser(validateThai: true);
            $content = "Hello\nWorld\nTest";

            expect($parser->supports($content))->toBeFalse();
        });

        it('returns true for content without Thai when validation disabled', function () {
            $parser = new PlainTextParser(validateThai: false);
            $content = "Hello\nWorld\nTest";

            expect($parser->supports($content))->toBeTrue();
        });
    });

    describe('getType', function () {
        it('returns plain_text type', function () {
            $parser = new PlainTextParser;

            expect($parser->getType())->toBe('plain_text');
        });

        it('returns same type regardless of configuration', function () {
            $parser1 = new PlainTextParser(validateThai: true);
            $parser2 = new PlainTextParser(validateThai: false, minLength: 5);

            expect($parser1->getType())->toBe('plain_text');
            expect($parser2->getType())->toBe('plain_text');
        });
    });

    describe('validation logic', function () {
        it('validates Thai Unicode range correctly', function () {
            $parser = new PlainTextParser;
            $content = "ก\nข\nฮ\nเ\nแ\nโ\nใ\nไ"; // Various Thai characters

            $words = $parser->parse($content);

            expect($words)->toHaveCount(8);
        });

        it('rejects Latin characters when Thai validation enabled', function () {
            $parser = new PlainTextParser(validateThai: true);
            $content = "สวัสดี\nHello\nWorld";

            $words = $parser->parse($content);

            expect($words)
                ->toHaveCount(1)
                ->toContain('สวัสดี');
        });

        it('accepts mixed Thai-English when validation disabled', function () {
            $parser = new PlainTextParser(validateThai: false);
            $content = "สวัสดี\nHello\nสวัสดีครับ";

            $words = $parser->parse($content);

            expect($words)->toHaveCount(3);
        });

        it('handles words at exact min/max length boundaries', function () {
            $parser = new PlainTextParser(minLength: 2, maxLength: 4);

            // Too short
            $short = $parser->parse('ก');
            expect($short)->toBeEmpty();

            // Min boundary
            $min = $parser->parse('กข');
            expect($min)->toHaveCount(1);

            // Max boundary
            $max = $parser->parse('กขคง');
            expect($max)->toHaveCount(1);

            // Too long
            $long = $parser->parse('กขคงจ');
            expect($long)->toBeEmpty();
        });

        it('counts Thai characters correctly with mb_strlen', function () {
            $parser = new PlainTextParser(minLength: 3, maxLength: 5);
            $content = 'สวัสดี'; // 6 Thai characters

            $words = $parser->parse($content);

            expect($words)->toBeEmpty(); // Too long (6 > 5)
        });
    });

    describe('edge cases', function () {
        it('handles content with only whitespace', function () {
            $parser = new PlainTextParser;
            $content = "   \n\t\t\n   ";

            // After trimming, no valid words will be found, but won't throw exception
            $words = $parser->parse($content);
            expect($words)->toBeEmpty();
        });

        it('handles single word', function () {
            $parser = new PlainTextParser;
            $content = 'สวัสดี';

            $words = $parser->parse($content);

            expect($words)->toBe(['สวัสดี']);
        });

        it('handles words with Thai tone marks and vowels', function () {
            $parser = new PlainTextParser;
            $content = "ก้า\nเก๋\nแก่\nใกล้";

            $words = $parser->parse($content);

            expect($words)->toHaveCount(4);
        });

        it('handles Thai numbers (๐-๙)', function () {
            $parser = new PlainTextParser;
            $content = 'ปี๒๕๖๗';

            $words = $parser->parse($content);

            // Thai numbers are in the Thai Unicode range
            expect($words)->toContain('ปี๒๕๖๗');
        });

        it('preserves word order after sort and deduplication', function () {
            $parser = new PlainTextParser;
            $content = "ฮ\nค\nก\nค\nข";

            $words = $parser->parse($content);

            // Should be sorted and deduplicated
            expect($words)->toEqual(['ก', 'ข', 'ค', 'ฮ']);
        });
    });

    describe('configuration combinations', function () {
        it('can use all configuration options together', function () {
            $parser = new PlainTextParser(
                validateThai: true,
                minLength: 2,
                maxLength: 10
            );

            $content = "ก\nสวัสดี\nขอบคุณมากครับผม\nHello";

            $words = $parser->parse($content);

            expect($words)
                ->toContain('สวัสดี') // Valid
                ->not->toContain('ก') // Too short
                ->not->toContain('ขอบคุณมากครับผม') // Too long (11 chars)
                ->not->toContain('Hello'); // Not Thai
        });

        it('respects configuration when checking support', function () {
            $parser = new PlainTextParser(
                validateThai: true,
                minLength: 5,
                maxLength: 10
            );

            // Short Thai words won't make it support the content
            $content = "ก\nข\nค";

            expect($parser->supports($content))->toBeFalse();
        });

        it('allows extreme min/max values', function () {
            $parser = new PlainTextParser(
                validateThai: false,
                minLength: 1,
                maxLength: 100
            );

            $content = "a\n".str_repeat('b', 100);

            $words = $parser->parse($content);

            expect($words)->toHaveCount(2);
        });
    });
});
