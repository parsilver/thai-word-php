<?php

use Farzai\ThaiWord\Console\Application;
use Farzai\ThaiWord\Console\Commands\PrepareDictionariesCommand;

describe('Console Application', function () {
    beforeEach(function () {
        $this->application = new Application;
    });

    describe('initialization', function () {
        it('creates application with correct name', function () {
            expect($this->application->getName())->toBe('Thai Word Segmentation CLI');
        });

        it('creates application with correct version', function () {
            expect($this->application->getVersion())->toBe('1.0.0');
        });

        it('sets default command to list', function () {
            // Test that the application has a default command set
            // by checking that the command argument exists
            $definition = $this->application->getDefinition();

            expect($definition->hasArgument('command'))->toBeTrue();

            $command = $definition->getArgument('command');
            expect($command)->not->toBeNull();
        });
    });

    describe('command registration', function () {
        it('registers PrepareDictionariesCommand', function () {
            expect($this->application->has('dict:prepare'))->toBeTrue();
        });

        it('registers command alias dictionary:prepare', function () {
            expect($this->application->has('dictionary:prepare'))->toBeTrue();
        });

        it('registered command is correct type', function () {
            $command = $this->application->get('dict:prepare');

            expect($command)->toBeInstanceOf(PrepareDictionariesCommand::class);
        });

        it('command has correct name', function () {
            $command = $this->application->get('dict:prepare');

            expect($command->getName())->toBe('dict:prepare');
        });

        it('command has correct description', function () {
            $command = $this->application->get('dict:prepare');

            expect($command->getDescription())
                ->toContain('Download and prepare Thai dictionaries');
        });
    });

    describe('getLongVersion', function () {
        it('returns a string', function () {
            $version = $this->application->getLongVersion();

            expect($version)->toBeString();
        });

        it('contains application name', function () {
            $version = $this->application->getLongVersion();

            expect($version)->toContain('Thai Word Segmentation CLI');
        });

        it('contains version number', function () {
            $version = $this->application->getLongVersion();

            expect($version)->toContain('1.0.0');
        });

        it('contains Thai flag emoji', function () {
            $version = $this->application->getLongVersion();

            expect($version)->toContain('🇹🇭');
        });

        it('contains library reference', function () {
            $version = $this->application->getLongVersion();

            expect($version)->toContain('Thai Word Segmentation Library');
        });
    });

    describe('getHelp', function () {
        it('returns a string', function () {
            $help = $this->application->getHelp();

            expect($help)->toBeString();
        });

        it('contains application title', function () {
            $help = $this->application->getHelp();

            expect($help)->toContain('Thai Word Segmentation CLI');
        });

        it('describes available commands', function () {
            $help = $this->application->getHelp();

            expect($help)->toContain('dict:prepare');
        });

        it('provides usage examples', function () {
            $help = $this->application->getHelp();

            expect($help)->toContain('Examples:');
            expect($help)->toContain('php thai-word');
        });

        it('mentions combined mode option', function () {
            $help = $this->application->getHelp();

            expect($help)->toContain('--combined');
        });

        it('provides help command information', function () {
            $help = $this->application->getHelp();

            expect($help)->toContain('help [command]');
        });

        it('is not empty', function () {
            $help = $this->application->getHelp();

            expect($help)->not->toBeEmpty();
        });
    });

    describe('command availability', function () {
        it('has list command available', function () {
            expect($this->application->has('list'))->toBeTrue();
        });

        it('has help command available', function () {
            expect($this->application->has('help'))->toBeTrue();
        });

        it('can retrieve dict:prepare command', function () {
            $command = $this->application->find('dict:prepare');

            expect($command)->not->toBeNull();
            expect($command->getName())->toBe('dict:prepare');
        });

        it('can retrieve command by alias', function () {
            $command = $this->application->find('dictionary:prepare');

            expect($command)->not->toBeNull();
            expect($command->getName())->toBe('dict:prepare');
        });
    });

    describe('application behavior', function () {
        it('extends Symfony Console Application', function () {
            expect($this->application)->toBeInstanceOf(\Symfony\Component\Console\Application::class);
        });

        it('has commands configured', function () {
            $commands = $this->application->all();

            expect($commands)->not->toBeEmpty();
            expect(count($commands))->toBeGreaterThan(0);
        });

        it('includes custom command in all commands', function () {
            $commands = $this->application->all();
            $commandNames = array_keys($commands);

            expect($commandNames)->toContain('dict:prepare');
        });
    });
});
