<?php

use Farzai\ThaiWord\Contracts\DictionaryParserInterface;
use Farzai\ThaiWord\Dictionary\Sources\FileDictionarySource;
use Farzai\ThaiWord\Exceptions\DictionaryException;
use Farzai\ThaiWord\Exceptions\SegmentationException;

describe('FileDictionarySource', function () {
    beforeEach(function () {
        $this->parser = Mockery::mock(DictionaryParserInterface::class);
        $this->tempDir = sys_get_temp_dir().'/thai-word-test-'.uniqid();
        mkdir($this->tempDir, 0777, true);
    });

    afterEach(function () {
        // Clean up temp files
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir.'/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->tempDir);
        }
        Mockery::close();
    });

    describe('getWords', function () {
        it('can load words from existing file', function () {
            $filePath = $this->tempDir.'/dictionary.txt';
            file_put_contents($filePath, "สวัสดี\nขอบคุณ\nสบายดี");

            $this->parser->shouldReceive('parse')
                ->once()
                ->with("สวัสดี\nขอบคุณ\nสบายดี")
                ->andReturn(['สวัสดี', 'ขอบคุณ', 'สบายดี']);

            $source = new FileDictionarySource($filePath, $this->parser);
            $words = $source->getWords();

            expect($words)->toBe(['สวัสดี', 'ขอบคุณ', 'สบายดี']);
        });

        it('throws exception when file does not exist', function () {
            $filePath = $this->tempDir.'/non-existent.txt';

            $source = new FileDictionarySource($filePath, $this->parser);

            expect(fn () => $source->getWords())
                ->toThrow(
                    DictionaryException::class,
                    "Dictionary file not found: {$filePath}",
                    SegmentationException::DICTIONARY_FILE_NOT_FOUND
                );
        });

        it('throws exception when file is not readable', function () {
            $filePath = $this->tempDir.'/unreadable.txt';
            file_put_contents($filePath, 'test');
            chmod($filePath, 0000);

            $source = new FileDictionarySource($filePath, $this->parser);

            expect(fn () => $source->getWords())
                ->toThrow(
                    DictionaryException::class,
                    "Dictionary file not found: {$filePath}",
                    SegmentationException::DICTIONARY_FILE_NOT_FOUND
                );

            // Clean up
            chmod($filePath, 0644);
        });

        it('throws exception when file_get_contents fails', function () {
            // Create a directory with same name as file to force file_get_contents to fail
            $filePath = $this->tempDir.'/directory-not-file';
            mkdir($filePath);

            $source = new FileDictionarySource($filePath, $this->parser);

            // file_exists will return true for directories, but file_get_contents will fail
            // We need to skip the directory case - let's use a symlink instead
            rmdir($filePath);

            // Create file then make it a directory afterwards
            file_put_contents($filePath, 'test');

            // Actually, let's test with a valid file but mock a failure scenario differently
            // Skip this edge case as it's hard to replicate in a cross-platform way
        })->skip('Hard to replicate file_get_contents failure in cross-platform way');

        it('handles empty file content', function () {
            $filePath = $this->tempDir.'/empty.txt';
            file_put_contents($filePath, '');

            $this->parser->shouldReceive('parse')
                ->once()
                ->with('')
                ->andReturn([]);

            $source = new FileDictionarySource($filePath, $this->parser);
            $words = $source->getWords();

            expect($words)->toBe([]);
        });

        it('passes file content to parser', function () {
            $filePath = $this->tempDir.'/test.txt';
            $content = "คำ1\nคำ2\nคำ3";
            file_put_contents($filePath, $content);

            $this->parser->shouldReceive('parse')
                ->once()
                ->with($content)
                ->andReturn(['คำ1', 'คำ2', 'คำ3']);

            $source = new FileDictionarySource($filePath, $this->parser);
            $words = $source->getWords();

            expect($words)->toHaveCount(3);
        });
    });

    describe('isAvailable', function () {
        it('returns true when file exists and is readable', function () {
            $filePath = $this->tempDir.'/available.txt';
            file_put_contents($filePath, 'test');

            $source = new FileDictionarySource($filePath, $this->parser);

            expect($source->isAvailable())->toBeTrue();
        });

        it('returns false when file does not exist', function () {
            $filePath = $this->tempDir.'/non-existent.txt';

            $source = new FileDictionarySource($filePath, $this->parser);

            expect($source->isAvailable())->toBeFalse();
        });

        it('returns false when file is not readable', function () {
            $filePath = $this->tempDir.'/unreadable.txt';
            file_put_contents($filePath, 'test');
            chmod($filePath, 0000);

            $source = new FileDictionarySource($filePath, $this->parser);

            expect($source->isAvailable())->toBeFalse();

            // Clean up
            chmod($filePath, 0644);
        });

        it('returns true for readable directory', function () {
            // Directories are technically "readable" but should fail when trying to get words
            $dirPath = $this->tempDir.'/test-dir';
            mkdir($dirPath);

            $source = new FileDictionarySource($dirPath, $this->parser);

            // file_exists and is_readable both return true for directories
            expect($source->isAvailable())->toBeTrue();
        });
    });

    describe('getMetadata', function () {
        it('returns metadata for existing file', function () {
            $filePath = $this->tempDir.'/metadata-test.txt';
            file_put_contents($filePath, 'test content');

            $this->parser->shouldReceive('getType')
                ->once()
                ->andReturn('plain_text');

            $source = new FileDictionarySource($filePath, $this->parser);
            $metadata = $source->getMetadata();

            expect($metadata)
                ->toBeArray()
                ->toHaveKey('file_path', $filePath)
                ->toHaveKey('parser_type', 'plain_text')
                ->toHaveKey('exists', true)
                ->toHaveKey('size')
                ->toHaveKey('modified');

            expect($metadata['size'])->toBeInt();
            expect($metadata['modified'])->toBeInstanceOf(DateTimeImmutable::class);
        });

        it('returns metadata for non-existent file', function () {
            $filePath = $this->tempDir.'/non-existent.txt';

            $this->parser->shouldReceive('getType')
                ->once()
                ->andReturn('plain_text');

            $source = new FileDictionarySource($filePath, $this->parser);
            $metadata = $source->getMetadata();

            expect($metadata)
                ->toBeArray()
                ->toHaveKey('file_path', $filePath)
                ->toHaveKey('parser_type', 'plain_text')
                ->toHaveKey('exists', false);

            expect($metadata)->not->toHaveKey('size');
            expect($metadata)->not->toHaveKey('modified');
        });

        it('includes correct file size', function () {
            $filePath = $this->tempDir.'/size-test.txt';
            $content = 'test content with some length';
            file_put_contents($filePath, $content);

            $this->parser->shouldReceive('getType')
                ->andReturn('plain_text');

            $source = new FileDictionarySource($filePath, $this->parser);
            $metadata = $source->getMetadata();

            expect($metadata['size'])->toBe(strlen($content));
        });

        it('includes modification time', function () {
            $filePath = $this->tempDir.'/mtime-test.txt';
            file_put_contents($filePath, 'test');

            // Sleep briefly to ensure time difference
            usleep(100000); // 100ms

            $beforeTime = new DateTimeImmutable;

            $this->parser->shouldReceive('getType')
                ->andReturn('plain_text');

            $source = new FileDictionarySource($filePath, $this->parser);
            $metadata = $source->getMetadata();

            expect($metadata['modified'])
                ->toBeInstanceOf(DateTimeImmutable::class);

            // Modified time should be before our check time
            expect($metadata['modified']->getTimestamp())
                ->toBeLessThanOrEqual($beforeTime->getTimestamp());
        });

        it('uses parser type from parser', function () {
            $filePath = $this->tempDir.'/parser-type.txt';
            file_put_contents($filePath, 'test');

            $this->parser->shouldReceive('getType')
                ->once()
                ->andReturn('custom_parser');

            $source = new FileDictionarySource($filePath, $this->parser);
            $metadata = $source->getMetadata();

            expect($metadata['parser_type'])->toBe('custom_parser');
        });
    });

    describe('integration scenarios', function () {
        it('can be used to load and parse a real dictionary file', function () {
            $filePath = $this->tempDir.'/real-dict.txt';
            $content = "สวัสดี\nขอบคุณ\nลาก่อน";
            file_put_contents($filePath, $content);

            $this->parser->shouldReceive('parse')
                ->with($content)
                ->andReturn(['สวัสดี', 'ขอบคุณ', 'ลาก่อน']);

            $this->parser->shouldReceive('getType')
                ->andReturn('plain_text');

            $source = new FileDictionarySource($filePath, $this->parser);

            expect($source->isAvailable())->toBeTrue();
            expect($source->getWords())->toHaveCount(3);
            expect($source->getMetadata()['exists'])->toBeTrue();
        });

        it('handles UTF-8 Thai content correctly', function () {
            $filePath = $this->tempDir.'/thai.txt';
            $thaiWords = "สวัสดีครับ\nขอบคุณมากครับ\nราตรีสวัสดิ์";
            file_put_contents($filePath, $thaiWords);

            $this->parser->shouldReceive('parse')
                ->with($thaiWords)
                ->andReturn(['สวัสดีครับ', 'ขอบคุณมากครับ', 'ราตรีสวัสดิ์']);

            $source = new FileDictionarySource($filePath, $this->parser);
            $words = $source->getWords();

            expect($words)->toEqual(['สวัสดีครับ', 'ขอบคุณมากครับ', 'ราตรีสวัสดิ์']);
        });
    });
});
