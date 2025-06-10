<?php

namespace Farzai\ThaiWord\Contracts;

interface SegmenterInterface
{
    /**
     * Segment Thai text into an array of words
     *
     * @param  string  $text  The Thai text to segment
     * @return array<string> Array of segmented words
     *
     * @throws \Farzai\ThaiWord\Exceptions\SegmentationException
     */
    public function segment(string $text): array;

    /**
     * Segment Thai text and return as delimited string
     *
     * @param  string  $text  The Thai text to segment
     * @param  string  $delimiter  The delimiter to use between words
     * @return string Segmented text with delimiters
     *
     * @throws \Farzai\ThaiWord\Exceptions\SegmentationException
     */
    public function segmentToString(string $text, string $delimiter = '|'): string;
}
