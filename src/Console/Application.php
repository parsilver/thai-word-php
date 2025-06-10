<?php

declare(strict_types=1);

namespace Farzai\ThaiWord\Console;

use Farzai\ThaiWord\Console\Commands\PrepareDictionariesCommand;
use Symfony\Component\Console\Application as BaseApplication;

/**
 * Thai Word Segmentation Console Application
 *
 * This application provides CLI commands for managing Thai word segmentation
 * dictionaries and related operations.
 */
class Application extends BaseApplication
{
    private const NAME = 'Thai Word Segmentation CLI';

    private const VERSION = '1.0.0';

    public function __construct()
    {
        parent::__construct(self::NAME, self::VERSION);

        $this->registerCommands();
        $this->setDefaultCommand('list');
    }

    /**
     * Register all available commands
     */
    private function registerCommands(): void
    {
        $commands = [
            new PrepareDictionariesCommand,
        ];

        foreach ($commands as $command) {
            $this->add($command);
        }
    }

    /**
     * Get the long version string for the application
     */
    public function getLongVersion(): string
    {
        $version = parent::getLongVersion();

        return sprintf(
            '%s <info>%s</info> for Thai Word Segmentation Library',
            $version,
            '🇹🇭'
        );
    }

    /**
     * Get help text for the application
     */
    public function getHelp(): string
    {
        return <<<'HELP'
<info>Thai Word Segmentation CLI</info>

This tool provides commands for managing Thai word segmentation dictionaries
and performing related operations for the Thai Word Segmentation PHP library.

<comment>Available Commands:</comment>
• <info>dict:prepare</info> - Download and prepare Thai dictionaries from LibreOffice

<comment>Examples:</comment>
  <comment>php thai-word dict:prepare</comment>              # Prepare all dictionaries
  <comment>php thai-word dict:prepare --combined</comment>    # Prepare combined dictionary
  <comment>php thai-word list</comment>                       # Show all available commands

<comment>For more information about a specific command:</comment>
  <comment>php thai-word help [command]</comment>

HELP;
    }
}
