<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Farzai\ThaiWord\Algorithms\Strategies\LongestMatchingStrategy;
use Farzai\ThaiWord\Dictionary\HashDictionary;
use Farzai\ThaiWord\Segmenter\ThaiSegmenter;

/**
 * Advanced Configuration Example
 *
 * This example demonstrates advanced configuration options including:
 * - Different dictionary sources (file, URL, array)
 * - Custom word additions and removals
 * - Dictionary optimization and statistics
 * - Memory usage monitoring
 */
echo "=== Advanced Thai Word Segmentation Configuration ===\n\n";

try {
    // Create dictionary with custom configuration
    $dictionary = new HashDictionary;

    echo "1. Loading dictionary from multiple sources...\n";

    // Load from file source
    $dictionary->load(__DIR__.'/../resources/dictionaries/libreoffice-combined.txt');
    echo "   ✓ Loaded from file source\n";

    echo "2. Adding custom domain-specific words...\n";

    // Add technical terms
    $technicalTerms = [
        'โปรแกรมเมอร์',
        'เว็บไซต์',
        'ฐานข้อมูล',
        'ปัญญาประดิษฐ์',
        'คอมพิวเตอร์',
        'อินเทอร์เน็ต',
        'เทคโนโลยี',
    ];

    foreach ($technicalTerms as $term) {
        $added = $dictionary->add($term);
        echo '   '.($added ? '✓' : '→')." {$term}\n";
    }

    echo "\n3. Dictionary statistics:\n";
    $stats = $dictionary->getMemoryStats();
    printf("   Word count: %s\n", number_format($stats['word_count']));
    printf("   Max word length: %d characters\n", $stats['max_word_length']);
    printf("   Memory usage: %.2f MB\n", $stats['estimated_memory_mb']);
    echo "   Length distribution:\n";
    foreach ($stats['length_distribution'] as $length => $count) {
        printf("     %d chars: %s words\n", $length, number_format($count));
    }

    echo "\n4. Testing segmentation with different algorithms:\n";

    // Create segmenter with longest matching
    $longestMatching = new LongestMatchingStrategy;
    $segmenter = new ThaiSegmenter($dictionary, $longestMatching);

    $testTexts = [
        'ปัญญาประดิษฐ์จะช่วยพัฒนาเทคโนโลยี',
        'โปรแกรมเมอร์สร้างเว็บไซต์ใหม่',
        'ฐานข้อมูลจัดเก็บข้อมูลสำคัญ',
    ];

    foreach ($testTexts as $text) {
        echo "\nText: {$text}\n";

        $words = $segmenter->segment($text);
        echo 'Longest Matching: '.implode(' | ', $words)."\n";

        // Show character count analysis
        $charCount = mb_strlen($text, 'UTF-8');
        $wordCount = count($words);
        $avgWordLength = $wordCount > 0 ? round($charCount / $wordCount, 2) : 0;

        echo "Analysis: {$charCount} chars → {$wordCount} words (avg: {$avgWordLength} chars/word)\n";
    }

    echo "\n5. Testing dictionary operations:\n";

    // Test contains operation
    $testWords = ['สวัสดี', 'เทคโนโลยี', 'คำที่ไม่มีในพจนานุกรม'];
    foreach ($testWords as $word) {
        $exists = $dictionary->contains($word);
        echo "   '{$word}': ".($exists ? '✓ Found' : '✗ Not found')."\n";
    }

    // Test longest match functionality
    echo "\n6. Testing longest match functionality:\n";
    $text = 'สวัสดีครับผม';
    for ($pos = 0; $pos < mb_strlen($text, 'UTF-8'); $pos++) {
        $match = $dictionary->findLongestMatch($text, $pos, 10);
        $char = mb_substr($text, $pos, 1, 'UTF-8');
        echo "   Position {$pos} ('{$char}'): ".($match ?: 'no match')."\n";
    }

    echo "\n7. Performance optimization test:\n";

    $longText = str_repeat('สวัสดีครับผมชื่อสมชายใช้โปรแกรมเมอร์พัฒนาเว็บไซต์ ', 100);

    $startTime = microtime(true);
    $startMemory = memory_get_usage();

    $result = $segmenter->segment($longText);

    $endTime = microtime(true);
    $endMemory = memory_get_usage();

    $processingTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
    $memoryUsed = ($endMemory - $startMemory) / 1024 / 1024; // Convert to MB
    $wordCount = count($result);

    printf("   Processed %d words in %.2f ms\n", $wordCount, $processingTime);
    printf("   Memory used: %.2f MB\n", $memoryUsed);
    printf("   Processing speed: %.0f words/second\n", $wordCount / ($processingTime / 1000));

} catch (\Exception $e) {
    echo 'Error: '.$e->getMessage()."\n";
    echo "Stack trace:\n".$e->getTraceAsString()."\n";
}

echo "\n=== Advanced configuration example completed ===\n";
