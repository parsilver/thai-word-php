<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Farzai\ThaiWord\Composer;

/**
 * Basic Usage Example
 *
 * This example demonstrates the most basic way to use the Thai word segmentation library.
 * It shows how to create a segmenter and segment Thai text into words.
 */
echo "=== Basic Thai Word Segmentation Example ===\n\n";

try {
    // Example Thai texts to segment
    $texts = [
        'สวัสดีครับ',
        'ผมชื่อสมชาย',
        'ฉันกินข้าวแล้ว',
        'วันนี้อากาศดี',
        'ขอบคุณค่ะ',
    ];

    foreach ($texts as $text) {
        echo "Original text: {$text}\n";

        // Segment text into array of words using Composer facade
        $words = Composer::segment($text);
        echo 'Segmented words: '.implode(' | ', $words)."\n";

        // Alternative: Get segmented text as delimited string
        $segmentedString = Composer::segmentToString($text, ' | ');
        echo "Segmented string: {$segmentedString}\n";

        echo str_repeat('-', 50)."\n";
    }

    // Handle mixed Thai-English text
    echo "\n=== Mixed Thai-English Text ===\n";
    $mixedText = 'ผมใช้ Computer ทำงาน';
    $words = Composer::segment($mixedText);
    echo "Mixed text: {$mixedText}\n";
    echo 'Segmented: '.implode(' | ', $words)."\n";

} catch (\Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
    echo "Make sure the dictionary file exists and is readable.\n";
}

echo "\n=== Example completed ===\n";
