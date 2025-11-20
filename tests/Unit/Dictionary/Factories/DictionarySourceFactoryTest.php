<?php

use Farzai\ThaiWord\Contracts\DictionarySourceInterface;
use Farzai\ThaiWord\Dictionary\Config\DictionaryUrls;
use Farzai\ThaiWord\Dictionary\Factories\DictionarySourceFactory;

describe('DictionarySourceFactory', function () {

    it('creates LibreOffice dictionary source by type', function () {
        $source = DictionarySourceFactory::createLibreOfficeDictionary('main');

        expect($source)->toBeInstanceOf(DictionarySourceInterface::class);

        $metadata = $source->getMetadata();
        expect($metadata['url'])->toBe(DictionaryUrls::LIBREOFFICE_THAI_MAIN);
        expect($metadata['parser_type'])->toBe('libreoffice_main');
    });

    it('creates all LibreOffice dictionary types', function () {
        $types = ['main', 'typos_translit', 'typos_common'];

        foreach ($types as $type) {
            $source = DictionarySourceFactory::createLibreOfficeDictionary($type);

            expect($source)->toBeInstanceOf(DictionarySourceInterface::class);

            $metadata = $source->getMetadata();
            expect($metadata['url'])->toBe(DictionaryUrls::getLibreOfficeUrl($type));
            expect($metadata['parser_type'])->toBe('libreoffice_'.$type);
        }
    });

    it('creates source from generic create method', function () {
        $source = DictionarySourceFactory::create('libreoffice_main');

        expect($source)->toBeInstanceOf(DictionarySourceInterface::class);

        $metadata = $source->getMetadata();
        expect($metadata['url'])->toBe(DictionaryUrls::LIBREOFFICE_THAI_MAIN);
    });

    it('creates source from URL', function () {
        $customUrl = 'https://example.com/dictionary.txt';
        $source = DictionarySourceFactory::create('url', $customUrl);

        expect($source)->toBeInstanceOf(DictionarySourceInterface::class);

        $metadata = $source->getMetadata();
        expect($metadata['url'])->toBe($customUrl);
    });

    it('maintains backward compatibility', function () {
        // Test that old 'libreoffice' type still works
        $source = DictionarySourceFactory::create('libreoffice');

        expect($source)->toBeInstanceOf(DictionarySourceInterface::class);

        $metadata = $source->getMetadata();
        expect($metadata['url'])->toBe(DictionaryUrls::LIBREOFFICE_THAI_MAIN);
    });

    it('throws exception for unknown source type', function () {
        expect(fn () => DictionarySourceFactory::create('unknown'))
            ->toThrow(InvalidArgumentException::class, 'Unknown dictionary source type: unknown');
    });

    it('throws exception for unknown LibreOffice dictionary type', function () {
        expect(fn () => DictionarySourceFactory::createLibreOfficeDictionary('unknown'))
            ->toThrow(InvalidArgumentException::class, 'Unknown LibreOffice dictionary type: unknown');
    });

    it('respects headers options', function () {
        $source = DictionarySourceFactory::createLibreOfficeDictionary('main', 60, ['User-Agent' => 'Test']);

        expect($source)->toBeInstanceOf(DictionarySourceInterface::class);

        $metadata = $source->getMetadata();
        expect($metadata['headers'])->toHaveKey('User-Agent');
        expect($metadata['headers']['User-Agent'])->toBe('Test');
    });
});
