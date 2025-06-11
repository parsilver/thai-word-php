<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

use Farzai\ThaiWord\Composer;
use Farzai\ThaiWord\Suggestions\Strategies\LevenshteinSuggestionStrategy;

echo "=== Facade with Word Suggestions Demo ===\n\n";

// Example 1: Enable suggestions via facade
echo "1. Basic Facade Usage with Suggestions\n";
echo str_repeat('-', 40)."\n";

// Enable suggestions using the facade
Composer::enableSuggestions([
    'threshold' => 0.6,
    'max_suggestions' => 3,
]);

// Use suggestion functionality through facade
$incorrectWord = 'สวัสด';
$suggestions = Composer::suggest($incorrectWord);

echo "Incorrect word: {$incorrectWord}\n";
echo "Suggestions via facade:\n";
foreach ($suggestions as $suggestion) {
    echo sprintf("  - %s (score: %.3f)\n", $suggestion['word'], $suggestion['score']);
}
echo "\n";

// Example 2: Segment with suggestions via facade
echo "2. Segmentation with Suggestions via Facade\n";
echo str_repeat('-', 40)."\n";

$text = 'สวัสดีครบผมชื่อโจน';
$result = Composer::segmentWithSuggestions($text);

echo "Original text: {$text}\n";
echo "Result via facade:\n";
foreach ($result as $item) {
    echo "  Word: {$item['word']}";
    if (isset($item['suggestions'])) {
        echo ' → Suggestions: ';
        $suggestionWords = array_map(fn ($s) => $s['word'], $item['suggestions']);
        echo implode(', ', array_slice($suggestionWords, 0, 2));
    }
    echo "\n";
}
echo "\n";

// Example 3: Custom suggestion strategy via facade
echo "3. Custom Suggestion Strategy via Facade\n";
echo str_repeat('-', 40)."\n";

// Create custom strategy
$customStrategy = new LevenshteinSuggestionStrategy;
$customStrategy->setThreshold(0.8)
    ->setMaxWordLengthDiff(1);

// Set custom strategy via facade
Composer::setSuggestionStrategy($customStrategy);

$testWord = 'ขอบคน';
$customSuggestions = Composer::suggest($testWord);

echo "Test word: {$testWord}\n";
echo "Custom strategy suggestions (threshold: 0.8):\n";
foreach ($customSuggestions as $suggestion) {
    echo sprintf("  - %s (score: %.3f)\n", $suggestion['word'], $suggestion['score']);
}
echo "\n";

// Example 4: Batch processing via facade
echo "4. Batch Processing with Suggestions\n";
echo str_repeat('-', 40)."\n";

$texts = ['สวัสดีครบ', 'ขอบคนครับ', 'ผมชื่อโจน'];

echo "Batch processing results:\n";
foreach ($texts as $i => $text) {
    echo 'Text '.($i + 1).": {$text}\n";

    $segments = Composer::segment($text);
    echo '  Normal segmentation: '.implode(' | ', $segments)."\n";

    $withSuggestions = Composer::segmentWithSuggestions($text);
    $hassuggestions = false;
    foreach ($withSuggestions as $item) {
        if (isset($item['suggestions'])) {
            echo "  Suggestion for '{$item['word']}': {$item['suggestions'][0]['word']}\n";
            $hassuggestions = true;
        }
    }
    if (! $hassuggestions) {
        echo "  No suggestions needed\n";
    }
    echo "\n";
}

// Example 5: Facade configuration management
echo "5. Configuration Management via Facade\n";
echo str_repeat('-', 40)."\n";

// Get current configuration
$config = Composer::getConfig();
echo "Current suggestion settings:\n";
echo '  Enable suggestions: '.($config['enable_suggestions'] ? 'Yes' : 'No')."\n";
echo "  Suggestion threshold: {$config['suggestion_threshold']}\n";
echo "  Max suggestions: {$config['max_suggestions']}\n";

// Update configuration
Composer::updateConfig([
    'suggestion_threshold' => 0.75,
    'max_suggestions' => 2,
]);

echo "\nAfter updating configuration:\n";
$newConfig = Composer::getConfig();
echo "  Suggestion threshold: {$newConfig['suggestion_threshold']}\n";
echo "  Max suggestions: {$newConfig['max_suggestions']}\n";
echo "\n";

// Example 6: Performance statistics
echo "6. Performance Statistics\n";
echo str_repeat('-', 40)."\n";

$stats = Composer::getStats();
echo "Facade performance statistics:\n";
echo sprintf("  Segments processed: %d\n", $stats['segments_processed']);
echo sprintf("  Cache hit ratio: %.2f%%\n", ($stats['cache_hit_ratio'] ?? 0) * 100);

$suggestionStrategy = Composer::getSuggestionStrategy();
if ($suggestionStrategy instanceof LevenshteinSuggestionStrategy) {
    $cacheStats = $suggestionStrategy->getCacheStats();
    echo sprintf("  Suggestion cache size: %d entries\n", $cacheStats['cache_size']);
}

// Example 7: Disable suggestions
echo "\n7. Disable Suggestions\n";
echo str_repeat('-', 40)."\n";

echo "Disabling suggestions...\n";
Composer::disableSuggestions();

$configAfterDisable = Composer::getConfig();
echo 'Suggestions enabled: '.($configAfterDisable['enable_suggestions'] ? 'Yes' : 'No')."\n";

// Try to use suggestions (should show no suggestions)
$resultWithoutSuggestions = Composer::segmentWithSuggestions('สวัสดีครบ');
$suggestionCount = 0;
foreach ($resultWithoutSuggestions as $item) {
    if (isset($item['suggestions'])) {
        $suggestionCount++;
    }
}
echo "Suggestions found: {$suggestionCount}\n";

echo "\n=== Facade Demo Completed ===\n";
