<?php

use Farzai\ThaiWord\Algorithms\Strategies\LongestMatchingStrategy;
use Farzai\ThaiWord\Dictionary\HashDictionary;
use Farzai\ThaiWord\Segmenter\ThaiSegmenter;

describe('Segmentation Integration Flow', function () {
    it('can perform end-to-end segmentation with file dictionary', function () {
        $dictionary = new HashDictionary;
        $dictionaryPath = __DIR__.'/../../resources/dictionaries/libreoffice-combined.txt';
        $dictionary->load($dictionaryPath);

        $algorithm = new LongestMatchingStrategy;
        $segmenter = new ThaiSegmenter($dictionary, $algorithm);

        $result = $segmenter->segment('สวัสดีครับขอบคุณมาก');

        expect($result)->toBe(['สวัสดี', 'ครับ', 'ขอบคุณ', 'มาก']);
    });

    it('can handle mixed known and unknown text', function () {
        $dictionary = new HashDictionary;
        $dictionary->add('สวัสดี');
        $dictionary->add('ครับ');

        $algorithm = new LongestMatchingStrategy;
        $segmenter = new ThaiSegmenter($dictionary, $algorithm);

        $result = $segmenter->segment('สวัสดีXYZครับ');

        // The optimized algorithm groups English characters together for better performance
        expect($result)->toBe(['สวัสดี', 'XYZ', 'ครับ']);
    });
});
