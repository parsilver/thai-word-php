<?php

use Farzai\ThaiWord\Algorithms\Strategies\LongestMatchingStrategy;
use Farzai\ThaiWord\Dictionary\HashDictionary;
use Farzai\ThaiWord\Exceptions\SegmentationException;
use Farzai\ThaiWord\Segmenter\ThaiSegmenter;

describe('ThaiSegmenter', function () {
    beforeEach(function () {
        $this->dictionary = new HashDictionary;
        $this->dictionary->add('สวัสดี');
        $this->dictionary->add('ครับ');

        $this->algorithm = new LongestMatchingStrategy;
        $this->segmenter = new ThaiSegmenter($this->dictionary, $this->algorithm);
    });

    it('can segment text', function () {
        $result = $this->segmenter->segment('สวัสดีครับ');

        expect($result)->toBe(['สวัสดี', 'ครับ']);
    });

    it('can segment to string with default delimiter', function () {
        $result = $this->segmenter->segmentToString('สวัสดีครับ');

        expect($result)->toBe('สวัสดี|ครับ');
    });

    it('can segment to string with custom delimiter', function () {
        $result = $this->segmenter->segmentToString('สวัสดีครับ', ' ');

        expect($result)->toBe('สวัสดี ครับ');
    });

    it('returns empty array for empty input', function () {
        $result = $this->segmenter->segment('');

        expect($result)->toBe([]);
    });

    it('throws exception for invalid UTF-8 input', function () {
        expect(fn () => $this->segmenter->segment("\xFF\xFE"))
            ->toThrow(SegmentationException::class, null, SegmentationException::INPUT_INVALID_ENCODING);
    });
});
