<?php

use Farzai\ThaiWord\Algorithms\Strategies\LongestMatchingStrategy;
use Farzai\ThaiWord\Dictionary\HashDictionary;
use Farzai\ThaiWord\Exceptions\AlgorithmException;
use Farzai\ThaiWord\Exceptions\SegmentationException;

describe('LongestMatchingStrategy', function () {
    beforeEach(function () {
        $this->algorithm = new LongestMatchingStrategy;
        $this->dictionary = new HashDictionary;
        $this->dictionary->add('สวัสดี');
        $this->dictionary->add('ครับ');
        $this->dictionary->add('สวัส');
    });

    it('can segment simple text', function () {
        $result = $this->algorithm->process('สวัสดีครับ', $this->dictionary);

        expect($result)->toBe(['สวัสดี', 'ครับ']);
    });

    it('prioritizes longest match', function () {
        $result = $this->algorithm->process('สวัสดี', $this->dictionary);

        expect($result)->toBe(['สวัสดี']);
    });

    it('handles unknown characters', function () {
        $result = $this->algorithm->process('สวัสดีXครับ', $this->dictionary);

        expect($result)->toBe(['สวัสดี', 'X', 'ครับ']);
    });

    it('returns empty array for empty input', function () {
        $result = $this->algorithm->process('', $this->dictionary);

        expect($result)->toBe([]);
    });

    it('throws exception for invalid UTF-8 input', function () {
        expect(fn () => $this->algorithm->process("\xFF\xFE", $this->dictionary))
            ->toThrow(AlgorithmException::class, null, SegmentationException::INPUT_INVALID_ENCODING);
    });

    describe('mixed content', function () {
        it('handles Thai and English mixed text', function () {
            $this->dictionary->add('hello');

            $result = $this->algorithm->process('สวัสดีhelloครับ', $this->dictionary);

            expect($result)->toContain('สวัสดี', 'hello', 'ครับ');
        });

        it('handles Thai and numbers', function () {
            $result = $this->algorithm->process('สวัสดี123ครับ', $this->dictionary);

            expect($result)->toBeArray()->not->toBeEmpty();
            expect($result)->toContain('สวัสดี', 'ครับ');
        });

        it('handles Thai with punctuation', function () {
            $result = $this->algorithm->process('สวัสดี!ครับ.', $this->dictionary);

            expect($result)->toBeArray()->toContain('สวัสดี', 'ครับ');
        });

        it('handles whitespace between words', function () {
            $result = $this->algorithm->process('สวัสดี ครับ', $this->dictionary);

            expect($result)->toBeArray();
            expect($result)->toContain('สวัสดี', 'ครับ');
        });

        it('handles multiple spaces', function () {
            $result = $this->algorithm->process('สวัสดี   ครับ', $this->dictionary);

            expect($result)->toBeArray()->not->toBeEmpty();
        });

        it('handles English words', function () {
            $result = $this->algorithm->process('Hello World', $this->dictionary);

            expect($result)->toBeArray()->not->toBeEmpty();
        });

        it('handles mixed Thai English and numbers', function () {
            $result = $this->algorithm->process('สวัสดี123helloครับ456', $this->dictionary);

            expect($result)->toBeArray()->not->toBeEmpty();
        });
    });

    describe('edge cases', function () {
        it('handles very long Thai text', function () {
            $longText = str_repeat('สวัสดีครับ', 100);

            $result = $this->algorithm->process($longText, $this->dictionary);

            expect($result)->toBeArray()->not->toBeEmpty();
        });

        it('handles single character', function () {
            $result = $this->algorithm->process('ส', $this->dictionary);

            expect($result)->toBeArray()->toHaveCount(1);
        });

        it('handles text with only unknown characters', function () {
            $result = $this->algorithm->process('XYZ', $this->dictionary);

            expect($result)->toBeArray()->not->toBeEmpty();
        });

        it('handles Thai text with tone marks', function () {
            $this->dictionary->add('ก้า');
            $this->dictionary->add('เก๋');

            $result = $this->algorithm->process('ก้าเก๋', $this->dictionary);

            expect($result)->toContain('ก้า', 'เก๋');
        });

        it('handles text starting with whitespace', function () {
            $result = $this->algorithm->process('  สวัสดีครับ', $this->dictionary);

            expect($result)->toBeArray();
        });

        it('handles text ending with whitespace', function () {
            $result = $this->algorithm->process('สวัสดีครับ  ', $this->dictionary);

            expect($result)->toBeArray();
        });

        it('handles whitespace-only text', function () {
            $result = $this->algorithm->process('   ', $this->dictionary);

            expect($result)->toBeEmpty();
        });
    });

    describe('character classification', function () {
        it('handles special characters', function () {
            $result = $this->algorithm->process('สวัสดี@#$ครับ', $this->dictionary);

            expect($result)->toBeArray()->not->toBeEmpty();
        });

        it('handles newlines', function () {
            $result = $this->algorithm->process("สวัสดี\nครับ", $this->dictionary);

            expect($result)->toBeArray()->not->toBeEmpty();
        });

        it('handles tabs', function () {
            $result = $this->algorithm->process("สวัสดี\tครับ", $this->dictionary);

            expect($result)->toBeArray()->not->toBeEmpty();
        });

        it('handles unicode punctuation', function () {
            $result = $this->algorithm->process('สวัสดี—ครับ', $this->dictionary);

            expect($result)->toBeArray()->not->toBeEmpty();
        });
    });

    describe('dictionary optimization', function () {
        it('adapts to dictionary max word length', function () {
            $this->dictionary->add('verylongthaiword');

            $result = $this->algorithm->process('สวัสดีครับ', $this->dictionary);

            expect($result)->toBeArray()->not->toBeEmpty();
        });

        it('handles empty dictionary', function () {
            $emptyDict = new HashDictionary;

            $result = $this->algorithm->process('สวัสดีครับ', $emptyDict);

            expect($result)->toBeArray()->not->toBeEmpty();
        });

        it('uses longest match from dictionary', function () {
            $this->dictionary->add('สวัสดีครับ');

            $result = $this->algorithm->process('สวัสดีครับ', $this->dictionary);

            // Should prefer the longest match
            expect($result)->toContain('สวัสดีครับ');
        });
    });

    describe('performance and caching', function () {
        it('handles repeated text efficiently', function () {
            // Process same text multiple times (should use cache)
            $result1 = $this->algorithm->process('สวัสดีครับ', $this->dictionary);
            $result2 = $this->algorithm->process('สวัสดีครับ', $this->dictionary);

            expect($result1)->toEqual($result2);
        });

        it('handles many small segments', function () {
            $this->dictionary->add('ก');
            $this->dictionary->add('ข');
            $this->dictionary->add('ค');

            $result = $this->algorithm->process(str_repeat('กขค', 50), $this->dictionary);

            expect($result)->toBeArray()->not->toBeEmpty();
        });
    });

    describe('real-world scenarios', function () {
        it('handles typical Thai sentence', function () {
            $this->dictionary->add('วันนี้');
            $this->dictionary->add('อากาศ');
            $this->dictionary->add('ดี');

            $result = $this->algorithm->process('สวัสดีครับวันนี้อากาศดี', $this->dictionary);

            expect($result)->toBeArray()->toContain('สวัสดี', 'ครับ');
        });

        it('handles URL-like text', function () {
            $result = $this->algorithm->process('เว็บไซต์http://example.comสวัสดี', $this->dictionary);

            expect($result)->toBeArray()->not->toBeEmpty();
        });

        it('handles email-like text', function () {
            $result = $this->algorithm->process('อีเมลtest@example.comสวัสดี', $this->dictionary);

            expect($result)->toBeArray()->not->toBeEmpty();
        });

        it('handles mixed Thai and number formats', function () {
            $result = $this->algorithm->process('ราคา1,234.56บาท', $this->dictionary);

            expect($result)->toBeArray()->not->toBeEmpty();
        });
    });

    describe('boundary conditions', function () {
        it('handles single Thai word', function () {
            $result = $this->algorithm->process('สวัสดี', $this->dictionary);

            expect($result)->toContain('สวัสดี');
        });

        it('handles two word boundary', function () {
            $result = $this->algorithm->process('สวัสดีครับ', $this->dictionary);

            expect($result)->toHaveCount(2);
        });

        it('handles alternating known and unknown characters', function () {
            $result = $this->algorithm->process('สXวXัXสXดXี', $this->dictionary);

            expect($result)->toBeArray()->not->toBeEmpty();
        });
    });
});
