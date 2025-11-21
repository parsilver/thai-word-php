<?php

declare(strict_types=1);

namespace Farzai\ThaiWord\Console\Commands;

use Farzai\ThaiWord\Dictionary\Factories\DictionarySourceFactory;
use Farzai\ThaiWord\Exceptions\DictionaryException;
use Farzai\ThaiWord\Services\DictionaryLoaderService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Command to prepare Thai dictionaries from LibreOffice repository
 *
 * This command downloads and prepares all LibreOffice Thai dictionaries
 * for use with the Thai Word Segmentation Library following Symfony Console best practices.
 *
 * The command supports two modes:
 * 1. Individual preparation - Downloads each dictionary separately
 * 2. Combined preparation - Downloads all dictionaries and creates a combined file
 *
 * Features:
 * - Progress tracking with visual feedback
 * - Error handling and recovery
 * - Force overwrite capability
 * - Custom timeout configuration
 * - Flexible directory configuration
 * - File size reporting and statistics
 * - Source availability checking
 * - Metadata reporting
 */
#[AsCommand(
    name: 'dict:prepare',
    description: 'Download and prepare Thai dictionaries from LibreOffice repository',
    aliases: ['dictionary:prepare']
)]
class PrepareDictionariesCommand extends Command
{
    /** Default directory for storing dictionary files relative to project root */
    private const DICTIONARIES_DIR = 'resources/dictionaries';

    /** Default timeout for HTTP requests in seconds */
    private const DEFAULT_TIMEOUT = 30;

    /** Filename for the combined dictionary containing all dictionaries merged */
    private const COMBINED_FILENAME = 'libreoffice-combined.txt';

    /** Symfony console style helper for formatted output */
    private SymfonyStyle $io;

    /** Resolved absolute path to the dictionaries directory */
    private string $dictionariesPath;

    /** Dictionary loader service for centralized loading */
    private DictionaryLoaderService $loaderService;

    /**
     * Configure command options and help text
     *
     * Sets up all available command options including:
     * - timeout: HTTP request timeout
     * - force: Force overwrite existing files
     * - combined: Use combined preparation mode
     * - dictionary-dir: Custom directory path
     * - keep-individual: Keep individual files when using combined mode
     */
    protected function configure(): void
    {
        $this
            ->setHelp($this->getCommandHelp())
            ->addOption(
                'timeout',
                't',
                InputOption::VALUE_REQUIRED,
                'Connection timeout in seconds',
                self::DEFAULT_TIMEOUT
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force overwrite existing dictionary files'
            )
            ->addOption(
                'combined',
                'c',
                InputOption::VALUE_NONE,
                'Prepare all dictionaries in one operation and create combined dictionary'
            )
            ->addOption(
                'dictionary-dir',
                'd',
                InputOption::VALUE_REQUIRED,
                'Custom directory path for dictionaries',
                self::DICTIONARIES_DIR
            )
            ->addOption(
                'keep-individual',
                'k',
                InputOption::VALUE_NONE,
                'Keep individual dictionary files when using combined mode (default: remove individual files)'
            );
    }

    /**
     * Initialize command execution environment
     *
     * Sets up the SymfonyStyle helper and resolves the dictionary directory path.
     * This method is called before interact() and execute().
     *
     * @param  InputInterface  $input  Command input interface
     * @param  OutputInterface  $output  Command output interface
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        // Initialize styled output helper for better UX
        $this->io = new SymfonyStyle($input, $output);

        // Resolve dictionary directory path (absolute or relative to project root)
        $this->dictionariesPath = $this->resolveDirectoryPath(
            $input->getOption('dictionary-dir')
        );

        // Initialize loader service
        $this->loaderService = new DictionaryLoaderService(new DictionarySourceFactory);
    }

    /**
     * Interactive command setup and configuration display
     *
     * Shows the command banner and current configuration to the user
     * before execution begins. This provides transparency about what
     * the command will do.
     *
     * @param  InputInterface  $input  Command input interface
     * @param  OutputInterface  $output  Command output interface
     */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        // Display welcome banner with dictionary information
        $this->displayBanner();

        // Show current configuration settings to user
        $this->displayConfiguration($input);
    }

    /**
     * Main command execution logic
     *
     * Determines whether to run individual or combined preparation
     * based on the --combined option. Handles all errors gracefully
     * and returns appropriate exit codes.
     *
     * @param  InputInterface  $input  Command input interface
     * @param  OutputInterface  $output  Command output interface
     * @return int Command exit code (SUCCESS or FAILURE)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            // Ensure the target directory exists before proceeding
            $this->ensureDirectoryExists();

            // Extract and normalize all command options
            $options = $this->extractOptions($input);

            // Choose execution path based on combined mode setting
            return $options['combined']
                ? $this->executeCombinedPreparation($options)
                : $this->executeIndividualPreparation($options);

        } catch (\Throwable $e) {
            // Handle any unexpected errors during execution
            return $this->handleError($e, $output);
        }
    }

    /**
     * Extract and normalize command options into an array
     *
     * Converts command line options into a structured array
     * for easier handling throughout the command execution.
     *
     * @param  InputInterface  $input  Command input interface
     * @return array<string, mixed> Normalized options array
     */
    private function extractOptions(InputInterface $input): array
    {
        return [
            'timeout' => (int) $input->getOption('timeout'),
            'force' => $input->getOption('force'),
            'combined' => $input->getOption('combined'),
            'keep-individual' => $input->getOption('keep-individual'),
        ];
    }

    /**
     * Display welcome banner and dictionary information
     *
     * Shows the command title and lists all available dictionaries
     * that will be downloaded from the LibreOffice repository.
     */
    private function displayBanner(): void
    {
        $this->io->title('Thai Word Segmentation - Dictionary Preparation');
        $this->io->text([
            'This tool downloads Thai dictionaries from LibreOffice repository:',
            '• Main Thai dictionary (th_TH.dic)',
            '• Typos transliteration dictionary (typos-translit.txt)',
            '• Common typos dictionary (typos-common.txt)',
        ]);
        $this->io->newLine();
    }

    /**
     * Handle and display errors with appropriate detail level
     *
     * Provides user-friendly error messages and optionally shows
     * stack traces in verbose mode for debugging purposes.
     *
     * @param  \Throwable  $e  The exception that occurred
     * @param  OutputInterface  $output  Command output interface
     * @return int Always returns FAILURE exit code
     */
    private function handleError(\Throwable $e, OutputInterface $output): int
    {
        // Display user-friendly error message
        $this->io->error([
            'Dictionary preparation failed:',
            $e->getMessage(),
        ]);

        // Show detailed stack trace only in very verbose mode
        if ($output->isVeryVerbose()) {
            $this->io->text('Stack trace:');
            $this->io->text($e->getTraceAsString());
        }

        return Command::FAILURE;
    }

    /**
     * Execute individual dictionary preparation mode
     *
     * Downloads each dictionary separately and provides individual
     * progress tracking and error reporting for each dictionary.
     *
     * @param  array<string, mixed>  $options  Command options
     * @return int Command exit code
     */
    private function executeIndividualPreparation(array $options): int
    {
        // Get configuration for all available dictionaries
        $dictionaries = $this->getDictionaryConfigurations();

        // Process each dictionary and collect results
        $results = $this->processDictionaries($dictionaries, $options);

        // Display final results and determine exit code
        return $this->displayResults($results['success'], $results['total']);
    }

    /**
     * Process multiple dictionaries with progress tracking
     *
     * Iterates through all dictionary configurations and attempts
     * to download each one. Tracks success/failure counts and
     * displays progress to the user.
     *
     * @param  array<string, array>  $dictionaries  Dictionary configurations
     * @param  array<string, mixed>  $options  Command options
     * @return array{success: int, total: int} Processing results
     */
    private function processDictionaries(array $dictionaries, array $options): array
    {
        $successCount = 0;
        $totalCount = count($dictionaries);

        // Initialize progress bar for visual feedback
        $this->io->progressStart($totalCount);

        // Process each dictionary configuration
        foreach ($dictionaries as $name => $config) {
            if ($this->processSingleDictionary($name, $config, $options)) {
                $successCount++;
            }
            $this->io->progressAdvance();
        }

        // Complete progress bar display
        $this->io->progressFinish();

        return ['success' => $successCount, 'total' => $totalCount];
    }

    /**
     * Process a single dictionary with error handling
     *
     * Attempts to download and prepare one dictionary file.
     * Catches and reports any errors without stopping the
     * overall process.
     *
     * @param  string  $name  Dictionary identifier name
     * @param  array<string, mixed>  $config  Dictionary configuration
     * @param  array<string, mixed>  $options  Command options
     * @return bool True if successful, false if failed
     */
    private function processSingleDictionary(string $name, array $config, array $options): bool
    {
        try {
            // Attempt to prepare the dictionary using new architecture
            $this->prepareDictionary($name, $config, $options['timeout'], $options['force']);

            return true;
        } catch (\Exception $e) {
            // Log error but continue with other dictionaries
            $this->io->error("Failed to prepare {$name}: ".$e->getMessage());

            return false;
        }
    }

    /**
     * Execute combined dictionary preparation mode
     *
     * Downloads all dictionaries in one operation, saves them
     * individually, creates a combined dictionary, and cleans
     * up temporary files.
     *
     * @param  array<string, mixed>  $options  Command options
     * @return int Command exit code
     */
    private function executeCombinedPreparation(array $options): int
    {
        $this->io->section('Combined Dictionary Preparation');

        try {
            // Create progress bar for 4 steps: 3 dictionaries + 1 combined
            $progressBar = $this->createProgressBar(4);

            // Step 1: Load all dictionaries from remote sources using new architecture
            $allDictionaries = $this->loadAllDictionaries($options['timeout'], $progressBar);

            // Step 2: Save each dictionary type separately
            $this->saveDictionariesSeparately($allDictionaries, $options['force'], $progressBar);

            // Step 3: Create combined dictionary from all sources
            $this->createCombinedDictionary($allDictionaries, $options['force']);

            // Complete progress tracking
            $progressBar->finish();

            // Step 4: Clean up individual dictionary files (unless requested to keep them)
            $this->cleanupIndividualDictionaries($options['keep-individual']);

            $this->io->success('Combined dictionary preparation completed successfully!');

            return Command::SUCCESS;

        } catch (DictionaryException $e) {
            // Handle dictionary-specific errors
            $this->io->error('Combined dictionary preparation failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Create and configure a progress bar for visual feedback
     *
     * Creates a progress bar with verbose formatting to show
     * detailed progress information during long operations.
     *
     * @param  int  $max  Maximum number of steps
     * @return ProgressBar Configured progress bar instance
     */
    private function createProgressBar(int $max): ProgressBar
    {
        $progressBar = new ProgressBar($this->io, $max);
        $progressBar->setFormat('verbose'); // Shows percentage and ETA
        $progressBar->start();

        return $progressBar;
    }

    /**
     * Load all dictionaries from remote LibreOffice repository using new architecture
     *
     * Uses the DictionarySourceFactory to create dictionary sources and fetch all dictionary
     * types in a single operation for efficiency.
     *
     * @param  int  $timeout  HTTP request timeout in seconds
     * @param  ProgressBar  $progressBar  Progress tracking instance
     * @return array<string, array<string>> Dictionary data by type
     */
    private function loadAllDictionaries(int $timeout, ProgressBar $progressBar): array
    {
        // Use new architecture to get all dictionary sources
        $sources = DictionarySourceFactory::getAllLibreOfficeDictionaries($timeout);

        $allDictionaries = [];
        foreach ($sources as $type => $source) {
            // Load words using centralized service
            $allDictionaries[$type] = $this->loaderService->loadFromDictionarySource($source);

            // Display metadata in verbose mode
            if ($this->io->isVerbose()) {
                $metadata = $source->getMetadata();
                $this->io->text("✅ Loaded {$type}: ".count($allDictionaries[$type]).' words from '.$metadata['url']);
            }
        }

        $progressBar->advance();

        return $allDictionaries;
    }

    /**
     * Save each dictionary type to separate files
     *
     * Iterates through the loaded dictionary data and saves
     * each type (main, typos_translit, typos_common) to
     * individual files with progress tracking.
     *
     * @param  array<string, array<string>>  $allDictionaries  Dictionary data
     * @param  bool  $force  Whether to overwrite existing files
     * @param  ProgressBar  $progressBar  Progress tracking instance
     */
    private function saveDictionariesSeparately(array $allDictionaries, bool $force, ProgressBar $progressBar): void
    {
        // Save each dictionary type to its own file
        foreach ($allDictionaries as $type => $words) {
            $this->saveDictionaryType($type, $words, $force);
            $progressBar->advance();
        }
    }

    /**
     * Save a specific dictionary type to file
     *
     * Creates a file for the specified dictionary type and
     * saves all words to it. Handles file existence checking
     * and provides user feedback.
     *
     * @param  string  $type  Dictionary type identifier
     * @param  array<string>  $words  Array of dictionary words
     * @param  bool  $force  Whether to overwrite existing files
     */
    private function saveDictionaryType(string $type, array $words, bool $force): void
    {
        // Generate filename based on dictionary type
        $filename = "libreoffice-{$type}.txt";
        $filePath = $this->dictionariesPath.'/'.$filename;

        // Check if file exists and force flag is not set
        if (! $force && file_exists($filePath)) {
            $this->io->note("Skipping {$type} (file exists, use --force to overwrite)");

            return;
        }

        // Save words to file
        $this->saveWordsToFile($words, $filePath);

        // Display success message with statistics
        $this->io->text(sprintf(
            '✅ %s: %d entries (%s)',
            ucfirst(str_replace('_', ' ', $type)), // Format type name for display
            count($words),
            $this->formatFileSize(filesize($filePath))
        ));
    }

    /**
     * Clean up individual dictionary files after combined creation
     *
     * Removes individual dictionary files after creating the
     * combined dictionary to avoid duplication and save disk space.
     * Only removes files if keepIndividual is false.
     *
     * @param  bool  $keepIndividual  Whether to keep individual files
     */
    private function cleanupIndividualDictionaries(bool $keepIndividual): void
    {
        // Skip cleanup if user wants to keep individual files
        if ($keepIndividual) {
            $this->io->note('Keeping individual dictionary files as requested');

            return;
        }

        // Define the individual dictionary files created by saveDictionaryType()
        $individualFiles = [
            'libreoffice-main.txt',
            'libreoffice-typos_translit.txt',
            'libreoffice-typos_common.txt',
        ];

        $removedCount = 0;

        // Remove each individual dictionary file
        foreach ($individualFiles as $filename) {
            $filePath = $this->dictionariesPath.'/'.$filename;
            if ($this->removeFileIfExists($filePath)) {
                $removedCount++;
            }
        }

        if ($removedCount > 0) {
            $this->io->note("Removed {$removedCount} individual dictionary files (combined version available)");
        }
    }

    /**
     * Safely remove a file if it exists
     *
     * Checks for file existence before attempting removal
     * to avoid errors and warnings.
     *
     * @param  string  $filePath  Path to the file to remove
     * @return bool True if file was removed, false if file didn't exist
     */
    private function removeFileIfExists(string $filePath): bool
    {
        if (file_exists($filePath)) {
            unlink($filePath);

            return true;
        }

        return false;
    }

    /**
     * Prepare a single dictionary by downloading and saving using new architecture
     *
     * Handles the complete process of preparing one dictionary:
     * checking existing files, downloading data, and saving to disk.
     *
     * @param  string  $name  Dictionary identifier name
     * @param  array<string, mixed>  $config  Dictionary configuration
     * @param  int  $timeout  HTTP request timeout in seconds
     * @param  bool  $force  Whether to overwrite existing files
     */
    private function prepareDictionary(string $name, array $config, int $timeout, bool $force): void
    {
        $filePath = $this->dictionariesPath.'/'.$config['filename'];

        // Check if we should skip this file
        if ($this->shouldSkipExistingFile($filePath, $force, $config['description'])) {
            return;
        }

        // Download and save the dictionary using new architecture
        $this->downloadAndSaveDictionary($config, $filePath, $timeout);
    }

    /**
     * Determine if an existing file should be skipped
     *
     * Checks if a file already exists and the force flag is not set.
     * Provides user feedback when skipping files.
     *
     * @param  string  $filePath  Path to the target file
     * @param  bool  $force  Whether to overwrite existing files
     * @param  string  $description  Human-readable description for feedback
     * @return bool True if file should be skipped, false otherwise
     */
    private function shouldSkipExistingFile(string $filePath, bool $force, string $description): bool
    {
        if (file_exists($filePath) && ! $force) {
            $this->io->note("Skipping {$description} (file exists, use --force to overwrite)");

            return true;
        }

        return false;
    }

    /**
     * Download dictionary data and save to file using new architecture
     *
     * Creates dictionary source, downloads data, saves to file,
     * and optionally displays performance statistics.
     *
     * @param  array<string, mixed>  $config  Dictionary configuration
     * @param  string  $filePath  Target file path for saving
     * @param  int  $timeout  HTTP request timeout in seconds
     */
    private function downloadAndSaveDictionary(array $config, string $filePath, int $timeout): void
    {
        $startTime = microtime(true);

        // Create dictionary source using new architecture
        $source = $this->createDictionarySource($config['type'], $timeout);

        // Load words using centralized service
        $words = $this->loaderService->loadFromDictionarySource($source);

        // Save words to the target file
        $this->saveWordsToFile($words, $filePath);

        // Display statistics in verbose mode
        if ($this->io->isVerbose()) {
            $this->displayDictionaryStats($config, $words, $filePath, $startTime, $source);
        }
    }

    /**
     * Create dictionary source using factory
     *
     * @param  string  $type  Dictionary type (main, typos_translit, typos_common)
     * @param  int  $timeout  HTTP timeout in seconds
     */
    private function createDictionarySource(string $type, int $timeout): \Farzai\ThaiWord\Contracts\DictionarySourceInterface
    {
        return DictionarySourceFactory::createLibreOfficeDictionary($type, $timeout);
    }

    /**
     * Save words array to file
     *
     * @param  array<string>  $words  Words to save
     * @param  string  $filePath  Target file path
     */
    private function saveWordsToFile(array $words, string $filePath): void
    {
        $directory = dirname($filePath);
        if (! is_dir($directory) && ! mkdir($directory, 0755, true)) {
            throw new DictionaryException("Cannot create directory: {$directory}");
        }

        $content = implode("\n", $words);
        $result = file_put_contents($filePath, $content);

        if ($result === false) {
            throw new DictionaryException("Failed to write dictionary to: {$filePath}");
        }
    }

    /**
     * Display statistics for a downloaded dictionary with source metadata
     *
     * Shows performance metrics including download time,
     * word count, file size, and source metadata for transparency and debugging.
     *
     * @param  array<string, mixed>  $config  Dictionary configuration
     * @param  array<string>  $words  Downloaded dictionary words
     * @param  string  $filePath  Path to the saved file
     * @param  float  $startTime  Start time for performance calculation
     * @param  \Farzai\ThaiWord\Contracts\DictionarySourceInterface  $source  Dictionary source
     */
    private function displayDictionaryStats(
        array $config,
        array $words,
        string $filePath,
        float $startTime,
        \Farzai\ThaiWord\Contracts\DictionarySourceInterface $source
    ): void {
        // Calculate download duration in milliseconds
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        $wordCount = count($words);
        $fileSize = $this->formatFileSize(filesize($filePath));

        // Get source metadata
        $metadata = $source->getMetadata();

        // Display formatted statistics
        $this->io->text(sprintf(
            '✅ %s: %d entries in %sms (%s)',
            $config['description'],
            $wordCount,
            $duration,
            $fileSize
        ));

        // Display additional metadata in very verbose mode
        if ($this->io->isVeryVerbose()) {
            $this->io->text([
                "   Source: {$metadata['url']}",
                "   Parser: {$metadata['parser_type']}",
                '   Content Length: '.($metadata['content_length'] ?? 'unknown').' bytes',
                '   Content Type: '.($metadata['content_type'] ?? 'unknown'),
            ]);
        }
    }

    /**
     * Create combined dictionary from all dictionary types
     *
     * Merges all dictionary types into a single file and
     * provides comprehensive statistics about the operation.
     *
     * @param  array<string, array<string>>  $allDictionaries  All dictionary data
     * @param  bool  $force  Whether to overwrite existing files
     */
    private function createCombinedDictionary(array $allDictionaries, bool $force): void
    {
        $combinedFilePath = $this->dictionariesPath.'/'.self::COMBINED_FILENAME;

        // Check if file exists and force flag is not set
        if (! $force && file_exists($combinedFilePath)) {
            $this->io->note('Skipping combined dictionary (file exists, use --force to overwrite)');

            return;
        }

        // Merge all dictionaries into one
        $combinedWords = $this->mergeDictionaries($allDictionaries);

        // Save combined dictionary
        $this->saveWordsToFile($combinedWords, $combinedFilePath);

        // Display comprehensive statistics
        $this->displayCombinedStats($allDictionaries, $combinedWords, $combinedFilePath);
    }

    /**
     * Display statistics for the combined dictionary
     *
     * @param  array<string, array<string>>  $allDictionaries  Original dictionary data
     * @param  array<string>  $combinedWords  Combined word list
     * @param  string  $filePath  Path to combined file
     */
    private function displayCombinedStats(array $allDictionaries, array $combinedWords, string $filePath): void
    {
        $totalOriginal = array_sum(array_map('count', $allDictionaries));
        $combinedCount = count($combinedWords);
        $duplicatesRemoved = $totalOriginal - $combinedCount;
        $fileSize = $this->formatFileSize(filesize($filePath));

        $this->io->text(sprintf(
            '✅ Combined Dictionary: %d entries (%s)',
            $combinedCount,
            $fileSize
        ));

        if ($this->io->isVerbose()) {
            $this->io->text([
                "   Original total: {$totalOriginal} entries",
                "   After deduplication: {$combinedCount} entries",
                "   Duplicates removed: {$duplicatesRemoved}",
            ]);
        }
    }

    /**
     * Merge all dictionaries into one deduplicated list
     *
     * Combines words from all dictionary types, removes duplicates,
     * and sorts the result alphabetically.
     *
     * @param  array<string, array<string>>  $allDictionaries  Dictionary data by type
     * @return array<string> Combined and deduplicated word list
     */
    private function mergeDictionaries(array $allDictionaries): array
    {
        $combinedWords = [];

        // Add main dictionary words first
        if (isset($allDictionaries['main'])) {
            $combinedWords = array_merge($combinedWords, $allDictionaries['main']);
        }

        // Add typos dictionaries
        foreach (['typos_translit', 'typos_common'] as $typosType) {
            if (isset($allDictionaries[$typosType])) {
                $combinedWords = array_merge($combinedWords, $allDictionaries[$typosType]);
            }
        }

        // Remove duplicates and sort alphabetically
        $combinedWords = array_unique($combinedWords);
        sort($combinedWords);

        return $combinedWords;
    }

    /**
     * Get configuration for all available dictionaries using new architecture
     *
     * Returns a structured array containing all dictionary
     * configurations for the new architecture.
     *
     * @return array<string, array<string, string>> Dictionary configurations
     */
    private function getDictionaryConfigurations(): array
    {
        return [
            'main' => [
                'type' => 'main',
                'filename' => 'libreoffice-thai.txt',
                'description' => 'Main LibreOffice Thai Dictionary',
            ],
            'typos_translit' => [
                'type' => 'typos_translit',
                'filename' => 'typos-translit.txt',
                'description' => 'LibreOffice Thai Typos Transliteration Dictionary',
            ],
            'typos_common' => [
                'type' => 'typos_common',
                'filename' => 'typos-common.txt',
                'description' => 'LibreOffice Thai Common Typos Dictionary',
            ],
        ];
    }

    /**
     * Display current command configuration to user
     *
     * Shows all configuration options in a formatted table
     * for transparency and confirmation before execution.
     *
     * @param  InputInterface  $input  Command input interface
     */
    private function displayConfiguration(InputInterface $input): void
    {
        $this->io->definitionList(
            ['Timeout' => $input->getOption('timeout').' seconds'],
            ['Force overwrite' => $input->getOption('force') ? 'Yes' : 'No'],
            ['Combined mode' => $input->getOption('combined') ? 'Yes' : 'No'],
            ['Dictionary directory' => $this->dictionariesPath],
            ['Keep individual files' => $input->getOption('keep-individual') ? 'Yes' : 'No']
        );
    }

    /**
     * Display final results and determine exit code
     *
     * Shows success/failure statistics and returns appropriate
     * exit code based on whether all operations succeeded.
     *
     * @param  int  $successCount  Number of successful operations
     * @param  int  $totalCount  Total number of operations attempted
     * @return int Command exit code (SUCCESS or FAILURE)
     */
    private function displayResults(int $successCount, int $totalCount): int
    {
        if ($successCount === $totalCount) {
            $this->io->success("All {$totalCount} dictionaries prepared successfully!");

            return Command::SUCCESS;
        }

        $this->io->warning("Dictionary preparation completed with issues: {$successCount}/{$totalCount} successful");

        return Command::FAILURE;
    }

    /**
     * Ensure the dictionaries directory exists
     *
     * Creates the target directory if it doesn't exist,
     * with appropriate permissions for file operations.
     *
     * @throws \RuntimeException If directory cannot be created
     */
    private function ensureDirectoryExists(): void
    {
        if (! is_dir($this->dictionariesPath)) {
            // Create directory with read/write/execute permissions for owner and group
            // Suppress warnings as we handle errors via return value check
            if (! @mkdir($this->dictionariesPath, 0755, true) && ! is_dir($this->dictionariesPath)) {
                throw new \RuntimeException("Cannot create dictionaries directory: {$this->dictionariesPath}");
            }
            $this->io->note("Created dictionaries directory: {$this->dictionariesPath}");
        }
    }

    /**
     * Resolve directory path to absolute path
     *
     * Converts relative paths to absolute paths based on
     * the project root directory (where composer.json is located).
     *
     * @param  string  $path  Directory path (absolute or relative)
     * @return string Resolved absolute directory path
     */
    private function resolveDirectoryPath(string $path): string
    {
        // If path starts with /, it's already absolute
        if (str_starts_with($path, '/')) {
            return $path;
        }

        // Convert relative path to absolute based on project root
        // Navigate up 3 levels from src/Console/Commands to project root
        $projectRoot = dirname(__DIR__, 3);

        return $projectRoot.'/'.$path;
    }

    /**
     * Format file size in human-readable format
     *
     * Converts bytes to appropriate units (B, KB, MB, GB)
     * with decimal precision for better readability.
     *
     * @param  int  $bytes  File size in bytes
     * @return string Formatted file size with units
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = 0;

        // Convert bytes to larger units until we reach appropriate scale
        while ($bytes >= 1024 && $factor < count($units) - 1) {
            $bytes /= 1024;
            $factor++;
        }

        return round($bytes, 2).' '.$units[$factor];
    }

    /**
     * Get comprehensive command help text
     *
     * Returns detailed help information including usage examples,
     * available options, and dictionary source information.
     *
     * @return string Formatted help text
     */
    private function getCommandHelp(): string
    {
        return <<<'HELP'
The <info>dict:prepare</info> command downloads and prepares Thai dictionaries from the LibreOffice repository.

<info>Usage examples:</info>

  # Prepare all dictionaries individually
  <comment>php thai-word dict:prepare</comment>

  # Force overwrite existing files with custom timeout
  <comment>php thai-word dict:prepare --force --timeout=60</comment>

  # Prepare all dictionaries in combined mode
  <comment>php thai-word dict:prepare --combined</comment>

  # Prepare combined dictionary but keep individual files
  <comment>php thai-word dict:prepare --combined --keep-individual</comment>

  # Use custom directory
  <comment>php thai-word dict:prepare --dictionary-dir=/path/to/custom/dir</comment>

<info>Dictionary Sources:</info>
- Main Thai dictionary (th_TH.dic)
- Typos transliteration dictionary (typos-translit.txt)  
- Common typos dictionary (typos-common.txt)

All dictionaries are downloaded from the official LibreOffice dictionaries repository.

<info>New Architecture:</info>
This command now uses the new dictionary architecture with:
- DictionarySourceFactory for creating sources
- Improved error handling and metadata reporting
- Source availability checking
- Better separation of concerns
HELP;
    }
}
