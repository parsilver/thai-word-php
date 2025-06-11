<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Farzai\ThaiWord\Segmenter\ThaiSegmenter;
use Farzai\ThaiWord\Suggestions\Strategies\LevenshteinSuggestionStrategy;

echo "=== Thai Word Segmentation with Suggestions ===\n\n";

// Example 1: Basic suggestion usage
echo "1. Basic Suggestion Usage\n";
echo str_repeat('-', 40)."\n";

$segmenter = new ThaiSegmenter;
$segmenter->enableSuggestions([
    'threshold' => 0.6,        // Minimum similarity score
    'max_suggestions' => 5,    // Maximum suggestions per word
]);

// Test with a potentially misspelled word
$incorrectWord = 'สวัสด';  // Missing last character
$suggestions = $segmenter->suggest($incorrectWord);

echo "Incorrect word: {$incorrectWord}\n";
echo "Suggestions:\n";
foreach ($suggestions as $suggestion) {
    echo sprintf("  - %s (score: %.3f)\n", $suggestion['word'], $suggestion['score']);
}
echo "\n";

// Example 2: Segmentation with suggestions for unrecognized words
echo "2. Segmentation with Suggestions\n";
echo str_repeat('-', 40)."\n";

$text = 'สวัสดีค๋บ'; // Contains 'ค๋บ' which might be unrecognized
$result = $segmenter->segmentWithSuggestions($text);

echo "Original text: {$text}\n";
echo "Segmentation with suggestions:\n";
foreach ($result as $item) {
    echo "  Word: {$item['word']}";
    if (isset($item['suggestions'])) {
        echo ' → Suggestions: ';
        $suggestionWords = array_map(fn ($s) => $s['word'], $item['suggestions']);
        echo implode(', ', array_slice($suggestionWords, 0, 3));
    }
    echo "\n";
}
echo "\n";

// Example 3: Custom suggestion strategy configuration
echo "3. Custom Suggestion Configuration\n";
echo str_repeat('-', 40)."\n";

// Create segmenter with custom suggestion strategy
$customStrategy = new LevenshteinSuggestionStrategy;
$customStrategy->setThreshold(0.8)          // Higher threshold for more accurate suggestions
    ->setMaxWordLengthDiff(2);   // Allow words with max 2 character difference

$customSegmenter = new ThaiSegmenter(null, null, $customStrategy);

$testWord = 'ขอบคน';  // Should suggest 'ขอบคุณ'
$customSuggestions = $customSegmenter->suggest($testWord);

echo "Test word: {$testWord}\n";
echo "Custom suggestions (threshold: 0.8):\n";
foreach ($customSuggestions as $suggestion) {
    echo sprintf("  - %s (score: %.3f)\n", $suggestion['word'], $suggestion['score']);
}
echo "\n";

// Example 4: Performance comparison
echo "4. Performance Comparison\n";
echo str_repeat('-', 40)."\n";

$longText = str_repeat('สวัสดีครบผมชื่อโจนส์มาจากอเมริกา ', 10);

// Without suggestions
$start = microtime(true);
$normalSegments = $segmenter->disableSuggestions()->segment($longText);
$timeWithoutSuggestions = microtime(true) - $start;

// With suggestions
$start = microtime(true);
$segmentWithSuggestions = $segmenter->enableSuggestions()->segmentWithSuggestions($longText);
$timeWithSuggestions = microtime(true) - $start;

echo "Performance comparison for long text:\n";
echo sprintf("  Without suggestions: %.4f seconds\n", $timeWithoutSuggestions);
echo sprintf("  With suggestions: %.4f seconds\n", $timeWithSuggestions);
echo sprintf("  Overhead: %.2fx\n", $timeWithSuggestions / $timeWithoutSuggestions);
echo "\n";

// Example 5: Batch processing with suggestions
echo "5. Batch Processing with Suggestions\n";
echo str_repeat('-', 40)."\n";

$texts = [
    'สวัสดีครบ',     // Contains potential typo
    'ขอบครณครับ',    // Contains potential typo
    'ผมชื่อโจน',     // Might need suggestions
];

echo "Batch processing results:\n";
foreach ($texts as $i => $text) {
    echo 'Text '.($i + 1).": {$text}\n";
    $result = $segmenter->segmentWithSuggestions($text);

    foreach ($result as $item) {
        if (isset($item['suggestions']) && ! empty($item['suggestions'])) {
            echo "  '{$item['word']}' → Suggested: '{$item['suggestions'][0]['word']}'\n";
        }
    }
    echo "\n";
}

// Example 6: Cache statistics and performance monitoring
echo "6. Cache Statistics\n";
echo str_repeat('-', 40)."\n";

$strategy = $segmenter->getSuggestionStrategy();
if ($strategy instanceof LevenshteinSuggestionStrategy) {
    $cacheStats = $strategy->getCacheStats();
    echo "Suggestion cache statistics:\n";
    echo sprintf("  Cache size: %d entries\n", $cacheStats['cache_size']);
    echo sprintf("  Max cache size: %d entries\n", $cacheStats['max_cache_size']);
    echo sprintf("  Memory usage: %.3f MB\n", $cacheStats['memory_usage_mb']);
}

// Get segmenter performance stats
$segmenterStats = $segmenter->getStats();
echo "\nSegmenter performance statistics:\n";
echo sprintf("  Segments processed: %d\n", $segmenterStats['segments_processed']);
echo sprintf("  Cache hit ratio: %.2f%%\n", ($segmenterStats['cache_hit_ratio'] ?? 0) * 100);
echo sprintf("  Average processing time: %.4f seconds\n", $segmenterStats['avg_processing_time'] ?? 0);

echo "\n=== Examples completed ===\n";
