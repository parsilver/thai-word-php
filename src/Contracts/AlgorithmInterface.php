<?php

namespace Farzai\ThaiWord\Contracts;

use Farzai\ThaiWord\Exceptions\AlgorithmException;

interface AlgorithmInterface
{
    /**
     * Process the text using the algorithm
     *
     * @param  string  $text  The text to process
     * @param  DictionaryInterface  $dictionary  The dictionary to use
     * @return array<string> Array of segmented words
     *
     * @throws AlgorithmException
     */
    public function process(string $text, DictionaryInterface $dictionary): array;
}
