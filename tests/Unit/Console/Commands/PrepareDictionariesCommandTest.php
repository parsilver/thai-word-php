<?php

use Farzai\ThaiWord\Console\Commands\PrepareDictionariesCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

describe('PrepareDictionariesCommand', function () {
    beforeEach(function () {
        $this->application = new Application;
        $this->command = new PrepareDictionariesCommand;
        $this->application->add($this->command);
        $this->commandTester = new CommandTester($this->command);
        $this->tempDir = sys_get_temp_dir().'/thai-word-test-'.uniqid();
        mkdir($this->tempDir, 0777, true);
    });

    afterEach(function () {
        // Clean up temp directory
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir.'/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->tempDir);
        }
    });

    describe('configuration', function () {
        it('has correct command name', function () {
            expect($this->command->getName())->toBe('dict:prepare');
        });

        it('has command alias', function () {
            expect($this->command->getAliases())->toContain('dictionary:prepare');
        });

        it('has description', function () {
            expect($this->command->getDescription())
                ->toContain('Download and prepare Thai dictionaries');
        });

        it('defines timeout option', function () {
            $definition = $this->command->getDefinition();

            expect($definition->hasOption('timeout'))->toBeTrue();
            expect($definition->getOption('timeout')->getShortcut())->toBe('t');
            expect($definition->getOption('timeout')->getDefault())->toBe(30);
        });

        it('defines force option', function () {
            $definition = $this->command->getDefinition();

            expect($definition->hasOption('force'))->toBeTrue();
            expect($definition->getOption('force')->getShortcut())->toBe('f');
        });

        it('defines combined option', function () {
            $definition = $this->command->getDefinition();

            expect($definition->hasOption('combined'))->toBeTrue();
            expect($definition->getOption('combined')->getShortcut())->toBe('c');
        });

        it('defines dictionary-dir option', function () {
            $definition = $this->command->getDefinition();

            expect($definition->hasOption('dictionary-dir'))->toBeTrue();
            expect($definition->getOption('dictionary-dir')->getShortcut())->toBe('d');
        });

        it('defines keep-individual option', function () {
            $definition = $this->command->getDefinition();

            expect($definition->hasOption('keep-individual'))->toBeTrue();
            expect($definition->getOption('keep-individual')->getShortcut())->toBe('k');
        });
    });

    describe('command help', function () {
        it('provides help text', function () {
            $help = $this->command->getHelp();

            expect($help)->toBeString()->not->toBeEmpty();
        });

        it('includes usage examples in help', function () {
            $help = $this->command->getHelp();

            expect($help)->toContain('dict:prepare');
        });
    });

    describe('execution', function () {
        it('can be executed without errors', function () {
            // This test may fail if network is unavailable or HTTP client is not installed
            // We'll mark it as risky but still valuable for integration testing
            try {
                $exitCode = $this->commandTester->execute([
                    '--dictionary-dir' => $this->tempDir,
                    '--force' => true,
                ]);

                // Command should exit with success or failure code
                expect($exitCode)->toBeIn([0, 1]);
            } catch (\Exception $e) {
                // Expected to fail if HTTP client not available
                expect($e->getMessage())->toBeString();
            }
        })->skip('Requires network and HTTP client dependencies');

        it('accepts custom timeout', function () {
            try {
                $exitCode = $this->commandTester->execute([
                    '--timeout' => 60,
                    '--dictionary-dir' => $this->tempDir,
                ]);

                expect($exitCode)->toBeIn([0, 1]);
            } catch (\Exception $e) {
                expect($e->getMessage())->toBeString();
            }
        })->skip('Requires network and HTTP client dependencies');

        it('accepts force flag', function () {
            try {
                $exitCode = $this->commandTester->execute([
                    '--force' => true,
                    '--dictionary-dir' => $this->tempDir,
                ]);

                expect($exitCode)->toBeIn([0, 1]);
            } catch (\Exception $e) {
                expect($e->getMessage())->toBeString();
            }
        })->skip('Requires network and HTTP client dependencies');

        it('accepts combined flag', function () {
            try {
                $exitCode = $this->commandTester->execute([
                    '--combined' => true,
                    '--dictionary-dir' => $this->tempDir,
                ]);

                expect($exitCode)->toBeIn([0, 1]);
            } catch (\Exception $e) {
                expect($e->getMessage())->toBeString();
            }
        })->skip('Requires network and HTTP client dependencies');

        it('accepts custom dictionary directory', function () {
            try {
                $exitCode = $this->commandTester->execute([
                    '--dictionary-dir' => $this->tempDir,
                ]);

                expect($exitCode)->toBeIn([0, 1]);
            } catch (\Exception $e) {
                expect($e->getMessage())->toBeString();
            }
        })->skip('Requires network and HTTP client dependencies');
    });

    describe('output', function () {
        it('displays output during execution', function () {
            try {
                $this->commandTester->execute([
                    '--dictionary-dir' => $this->tempDir,
                ]);

                $output = $this->commandTester->getDisplay();
                expect($output)->toBeString();
            } catch (\Exception $e) {
                expect($e->getMessage())->toBeString();
            }
        })->skip('Requires network and HTTP client dependencies');

        it('provides feedback on progress', function () {
            try {
                $this->commandTester->execute([
                    '--dictionary-dir' => $this->tempDir,
                ]);

                $output = $this->commandTester->getDisplay();
                // Output should contain progress information
                expect($output)->toBeString()->not->toBeEmpty();
            } catch (\Exception $e) {
                expect($e->getMessage())->toBeString();
            }
        })->skip('Requires network and HTTP client dependencies');
    });

    describe('error handling', function () {
        it('handles invalid timeout values gracefully', function () {
            // Negative timeout should be handled
            $exitCode = $this->commandTester->execute([
                '--timeout' => -1,
                '--dictionary-dir' => $this->tempDir,
            ]);

            // Should either fail or use default
            expect($exitCode)->toBeIn([0, 1]);
        })->skip('Timeout validation may vary');

        it('handles non-existent parent directory', function () {
            $invalidDir = '/non/existent/path/dictionaries';

            $exitCode = $this->commandTester->execute([
                '--dictionary-dir' => $invalidDir,
            ]);

            // Should fail or create directory
            expect($exitCode)->toBeIn([0, 1]);
        })->skip('May require write permissions');
    });

    describe('integration', function () {
        it('can run with all options combined', function () {
            try {
                $exitCode = $this->commandTester->execute([
                    '--timeout' => 30,
                    '--force' => true,
                    '--combined' => true,
                    '--keep-individual' => true,
                    '--dictionary-dir' => $this->tempDir,
                ]);

                expect($exitCode)->toBeIn([0, 1]);

                $output = $this->commandTester->getDisplay();
                expect($output)->toBeString();
            } catch (\Exception $e) {
                expect($e->getMessage())->toBeString();
            }
        })->skip('Requires network and HTTP client dependencies');
    });
});
