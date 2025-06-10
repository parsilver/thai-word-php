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
});
