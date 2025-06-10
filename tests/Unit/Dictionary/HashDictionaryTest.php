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
});
