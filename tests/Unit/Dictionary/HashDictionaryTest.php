<?php

use Farzai\ThaiWord\Dictionary\HashDictionary;
use Farzai\ThaiWord\Exceptions\DictionaryException;
use Farzai\ThaiWord\Exceptions\SegmentationException;

describe('HashDictionary', function () {
    beforeEach(function () {
        $this->dictionary = new HashDictionary;
    });

    it('can add words to dictionary', function () {
        $result = $this->dictionary->add('สวัสดี');

        expect($result)->toBeTrue();
        expect($this->dictionary->contains('สวัสดี'))->toBeTrue();
    });

    it('returns false when adding duplicate word', function () {
        $this->dictionary->add('สวัสดี');
        $result = $this->dictionary->add('สวัสดี');

        expect($result)->toBeFalse();
    });

    it('can remove words from dictionary', function () {
        $this->dictionary->add('สวัสดี');
        $result = $this->dictionary->remove('สวัสดี');

        expect($result)->toBeTrue();
        expect($this->dictionary->contains('สวัสดี'))->toBeFalse();
    });

    it('returns false when removing non-existent word', function () {
        $result = $this->dictionary->remove('สวัสดี');

        expect($result)->toBeFalse();
    });

    it('throws exception when adding empty word', function () {
        expect(fn () => $this->dictionary->add(''))
            ->toThrow(DictionaryException::class);
    });

    it('throws exception when adding invalid UTF-8 word', function () {
        expect(fn () => $this->dictionary->add("\xFF\xFE"))
            ->toThrow(DictionaryException::class);
    });

    it('throws exception when loading non-existent file', function () {
        expect(fn () => $this->dictionary->load('non-existent-file.txt'))
            ->toThrow(DictionaryException::class, null, SegmentationException::DICTIONARY_FILE_NOT_FOUND);
    });

    describe('getWords', function () {
        it('returns all words in dictionary', function () {
            $this->dictionary->add('สวัสดี');
            $this->dictionary->add('ขอบคุณ');
            $this->dictionary->add('สบายดี');

            $words = $this->dictionary->getWords();

            expect($words)
                ->toBeArray()
                ->toHaveCount(3)
                ->toContain('สวัสดี', 'ขอบคุณ', 'สบายดี');
        });

        it('returns empty array when dictionary is empty', function () {
            $words = $this->dictionary->getWords();

            expect($words)->toBeArray()->toBeEmpty();
        });

        it('reflects additions and removals', function () {
            $this->dictionary->add('สวัสดี');
            $this->dictionary->add('ขอบคุณ');

            expect($this->dictionary->getWords())->toHaveCount(2);

            $this->dictionary->remove('สวัสดี');

            expect($this->dictionary->getWords())->toHaveCount(1)->toContain('ขอบคุณ');
        });
    });

    describe('getWordCount', function () {
        it('returns correct word count', function () {
            expect($this->dictionary->getWordCount())->toBe(0);

            $this->dictionary->add('สวัสดี');
            expect($this->dictionary->getWordCount())->toBe(1);

            $this->dictionary->add('ขอบคุณ');
            expect($this->dictionary->getWordCount())->toBe(2);
        });

        it('decrements when removing words', function () {
            $this->dictionary->add('สวัสดี');
            $this->dictionary->add('ขอบคุณ');

            expect($this->dictionary->getWordCount())->toBe(2);

            $this->dictionary->remove('สวัสดี');

            expect($this->dictionary->getWordCount())->toBe(1);
        });

        it('does not change when adding duplicate', function () {
            $this->dictionary->add('สวัสดี');
            expect($this->dictionary->getWordCount())->toBe(1);

            $this->dictionary->add('สวัสดี');
            expect($this->dictionary->getWordCount())->toBe(1);
        });
    });

    describe('getMaxWordLength', function () {
        it('returns maximum word length', function () {
            $this->dictionary->add('ดี');        // 2 chars
            expect($this->dictionary->getMaxWordLength())->toBe(2);

            $this->dictionary->add('สวัสดี');    // 6 chars
            expect($this->dictionary->getMaxWordLength())->toBe(6);

            $this->dictionary->add('ขอบคุณมาก'); // 9 chars
            expect($this->dictionary->getMaxWordLength())->toBe(9);
        });

        it('returns 0 for empty dictionary', function () {
            expect($this->dictionary->getMaxWordLength())->toBe(0);
        });
    });

    describe('findLongestMatch', function () {
        it('finds longest matching word', function () {
            $this->dictionary->add('สวัส');
            $this->dictionary->add('สวัสดี');
            $this->dictionary->add('สวัสดีครับ');

            $match = $this->dictionary->findLongestMatch('สวัสดีครับผม', 0, 20);

            expect($match)->toBe('สวัสดีครับ');
        });

        it('returns null when no match found', function () {
            $this->dictionary->add('สวัสดี');

            $match = $this->dictionary->findLongestMatch('ขอบคุณ', 0, 10);

            expect($match)->toBeNull();
        });

        it('respects position parameter', function () {
            $this->dictionary->add('สดี');

            $match = $this->dictionary->findLongestMatch('สวัสดีครับ', 3, 10);

            expect($match)->toBe('สดี');
        });

        it('respects maxLength parameter', function () {
            $this->dictionary->add('สวัส');
            $this->dictionary->add('สวัสดี');

            // Only check up to 4 characters
            $match = $this->dictionary->findLongestMatch('สวัสดีครับ', 0, 4);

            expect($match)->toBe('สวัส');
        });

        it('prioritizes common word lengths', function () {
            // Add words of different lengths
            $this->dictionary->add('สวั');     // 3 chars - common length
            $this->dictionary->add('สวัสด');   // 5 chars - common length
            $this->dictionary->add('สวัสดีค'); // 7 chars - not common

            $match = $this->dictionary->findLongestMatch('สวัสดีครับ', 0, 10);

            expect($match)->toBe('สวัสดีค');
        });
    });

    describe('hasWordsWithPrefix', function () {
        it('returns true when words with prefix exist', function () {
            $this->dictionary->add('สวัสดี');
            $this->dictionary->add('สวัสดีครับ');
            $this->dictionary->add('สบายดี');

            expect($this->dictionary->hasWordsWithPrefix('สวัส'))->toBeTrue();
        });

        it('returns false when no words with prefix exist', function () {
            $this->dictionary->add('สวัสดี');

            expect($this->dictionary->hasWordsWithPrefix('ขอบ'))->toBeFalse();
        });

        it('returns true for empty prefix when dictionary has words', function () {
            $this->dictionary->add('สวัสดี');

            expect($this->dictionary->hasWordsWithPrefix(''))->toBeTrue();
        });

        it('returns false for empty prefix when dictionary is empty', function () {
            expect($this->dictionary->hasWordsWithPrefix(''))->toBeFalse();
        });

        it('handles exact word match', function () {
            $this->dictionary->add('สวัสดี');

            expect($this->dictionary->hasWordsWithPrefix('สวัสดี'))->toBeTrue();
        });
    });

    describe('getMemoryStats', function () {
        it('returns memory statistics', function () {
            $this->dictionary->add('สวัสดี');
            $this->dictionary->add('ขอบคุณ');

            $stats = $this->dictionary->getMemoryStats();

            expect($stats)
                ->toBeArray()
                ->toHaveKey('word_count')
                ->toHaveKey('max_word_length')
                ->toHaveKey('estimated_memory_mb')
                ->toHaveKey('length_distribution');

            expect($stats['word_count'])->toBe(2);
            expect($stats['max_word_length'])->toBeInt();
            expect($stats['estimated_memory_mb'])->toBeFloat();
            expect($stats['length_distribution'])->toBeArray();
        });

        it('includes length distribution', function () {
            $this->dictionary->add('ดี');      // 2 chars
            $this->dictionary->add('สวัสดี');  // 6 chars
            $this->dictionary->add('ขอบคุณ');  // 6 chars

            $stats = $this->dictionary->getMemoryStats();

            expect($stats['length_distribution'])
                ->toHaveKey(2, 1)  // One 2-char word
                ->toHaveKey(6, 2); // Two 6-char words
        });
    });

    describe('edge cases and optimization', function () {
        it('handles Thai characters with tone marks', function () {
            $this->dictionary->add('ก้า');
            $this->dictionary->add('เก๋');

            expect($this->dictionary->contains('ก้า'))->toBeTrue();
            expect($this->dictionary->contains('เก๋'))->toBeTrue();
        });

        it('handles large number of words efficiently', function () {
            for ($i = 0; $i < 1000; $i++) {
                $this->dictionary->add("word{$i}");
            }

            expect($this->dictionary->getWordCount())->toBe(1000);
            expect($this->dictionary->getWords())->toHaveCount(1000);
        });

        it('trims whitespace from added words', function () {
            $this->dictionary->add('  สวัสดี  ');

            expect($this->dictionary->contains('สวัสดี'))->toBeTrue();
            expect($this->dictionary->contains('  สวัสดี  '))->toBeFalse();
        });

        it('maintains statistics correctly', function () {
            $this->dictionary->add('ดี');
            $this->dictionary->add('สวัสดี');
            $this->dictionary->remove('ดี');

            $stats = $this->dictionary->getMemoryStats();

            expect($stats['word_count'])->toBe(1);
            expect($stats['length_distribution'])->not->toHaveKey(2);
        });
    });

    describe('batch operations', function () {
        it('handles multiple word additions efficiently', function () {
            $words = ['สวัสดี', 'ขอบคุณ', 'สบายดี', 'ลาก่อน'];

            foreach ($words as $word) {
                $this->dictionary->add($word);
            }

            expect($this->dictionary->getWordCount())->toBe(4);
        });

        it('skips duplicates in batch operations', function () {
            $this->dictionary->add('สวัสดี');
            $this->dictionary->add('สวัสดี');
            $this->dictionary->add('ขอบคุณ');

            expect($this->dictionary->getWordCount())->toBe(2);
        });
    });
});
