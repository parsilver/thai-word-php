<?php

use Farzai\ThaiWord\Algorithms\Strategies\LongestMatchingStrategy;
use Farzai\ThaiWord\Contracts\DictionaryInterface;
use Farzai\ThaiWord\Dictionary\HashDictionary;
use Farzai\ThaiWord\Segmenter\ThaiSegmenter;

describe('segmentation regressions', function () {
    beforeEach(function () {
        $this->dictionary = new HashDictionary;
        foreach (['ฉัน', 'รัก', 'ภาษา', 'ไทย', 'ภาษาไทย', 'มาก', 'ฟีฟ่า', 'ปี', 'ราคา', 'บาท', 'ก'] as $word) {
            $this->dictionary->add($word);
        }
        $this->algorithm = new LongestMatchingStrategy;
    });

    describe('long text', function () {
        it('does not duplicate characters in text without spaces', function () {
            $segmenter = new ThaiSegmenter($this->dictionary, $this->algorithm, null, ['enable_caching' => false]);
            $sentence = 'ฉันรักภาษาไทยมาก';
            $expected = array_merge(...array_fill(0, 100, $segmenter->segment($sentence)));

            $result = $segmenter->segment(str_repeat($sentence, 100));

            expect($result)->toBe($expected)
                ->and(implode('', $result))->toBe(str_repeat($sentence, 100));
        });

        it('does not duplicate characters in text with spaces', function () {
            $segmenter = new ThaiSegmenter($this->dictionary, $this->algorithm, null, ['enable_caching' => false]);
            $text = trim(str_repeat('ฉันรักภาษาไทยมาก ', 100));

            expect(implode('', $segmenter->segment($text)))->toBe($text);
        });
    });

    describe('character clusters', function () {
        it('keeps tone marks and vowels with their consonant for unknown words', function () {
            expect($this->algorithm->process('ฟีฟ่าซ์', $this->dictionary))->toBe(['ฟีฟ่า', 'ซ์']);
        });

        it('keeps a leading vowel with the next consonant', function () {
            expect($this->algorithm->process('เก', $this->dictionary))->toBe(['เก']);
        });

        it('keeps a doubled leading vowel in one cluster', function () {
            expect($this->algorithm->process('เเม่', $this->dictionary))->toBe(['เเม่']);
        });

        it('does not attach a leading vowel to a digit', function () {
            expect($this->algorithm->process('เ๑๒', $this->dictionary))->toBe(['เ', '๑๒']);
        });

        it('does not end a word before a following vowel or mark', function () {
            // "ก" is a word, but "กี่" must not be split into "ก" + "ี่"
            expect($this->algorithm->process('กี่', $this->dictionary))->toBe(['กี่']);
        });

        it('never emits a token that starts with a combining mark', function () {
            $result = $this->algorithm->process('ก่ข้ค์ฟีฟ่าซ์ษาภาษา', $this->dictionary);

            foreach ($result as $token) {
                expect(preg_match('/^[\x{0E30}-\x{0E3A}\x{0E45}\x{0E47}-\x{0E4E}]/u', $token))->toBe(0);
            }
            expect(implode('', $result))->toBe('ก่ข้ค์ฟีฟ่าซ์ษาภาษา');
        });
    });

    describe('numbers and latin text', function () {
        it('keeps Thai digit runs together', function () {
            expect($this->algorithm->process('ปี ๒๕๖๙', $this->dictionary))->toBe(['ปี', ' ', '๒๕๖๙']);
        });

        it('splits a sentence-final period from latin words and numbers', function () {
            expect($this->algorithm->process('ราคา 1,000.50. example.com.', $this->dictionary))
                ->toBe(['ราคา', ' ', '1,000.50', '.', ' ', 'example.com', '.']);
        });
    });

    describe('match cache', function () {
        it('uses words added to a plain dictionary after a previous call', function () {
            // No getMaxWordLength(), so the strategy keeps its default window
            $dictionary = new class implements DictionaryInterface
            {
                public array $words = ['สวัส' => true];

                public function load(string $source): void {}

                public function add(string $word): bool
                {
                    $this->words[$word] = true;

                    return true;
                }

                public function remove(string $word): bool
                {
                    unset($this->words[$word]);

                    return true;
                }

                public function contains(string $word): bool
                {
                    return isset($this->words[$word]);
                }

                public function getWords(): array
                {
                    return array_keys($this->words);
                }
            };

            expect($this->algorithm->process('สวัสดี', $dictionary))->toBe(['สวัส', 'ดี']);

            $dictionary->add('สวัสดี');

            expect($this->algorithm->process('สวัสดี', $dictionary))->toBe(['สวัสดี']);
        });

        it('does not reuse a cluster that is longer than the dictionary window', function () {
            expect($this->algorithm->process('กี่ กี ก', new HashDictionary))->toBe(['กี่', ' ', 'กี', ' ', 'ก']);
        });
    });
});

describe('HashDictionary consistency', function () {
    it('recomputes max word length after removing the longest word', function () {
        $dictionary = new HashDictionary;
        $dictionary->add('ก');
        $dictionary->add('ภาษาไทย');

        $dictionary->remove('ภาษาไทย');

        expect($dictionary->getMaxWordLength())->toBe(1);
    });

    it('strips Hunspell morphological fields when loading', function () {
        $file = tempnam(sys_get_temp_dir(), 'dict');
        file_put_contents($file, "กาชาด ph:กาชาติ\nกอปร/AB ph:กอร์ป\nemail\n");

        $dictionary = new HashDictionary;
        $dictionary->load($file);
        unlink($file);

        expect($dictionary->contains('กาชาด'))->toBeTrue()
            ->and($dictionary->contains('กอปร'))->toBeTrue()
            ->and($dictionary->contains('กาชาติ'))->toBeFalse()
            ->and($dictionary->getWordCount())->toBe(2);
    });
});

describe('ThaiSegmenter dictionary fallback', function () {
    it('reports that an injected dictionary is not the fallback', function () {
        $segmenter = new ThaiSegmenter(new HashDictionary);

        expect($segmenter->isUsingFallbackDictionary())->toBeFalse();
    });
});
