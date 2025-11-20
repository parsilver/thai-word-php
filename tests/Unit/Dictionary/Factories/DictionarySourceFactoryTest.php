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

    it('accepts headers parameter', function () {
        $headers = ['User-Agent' => 'Test'];
        $source = DictionarySourceFactory::createLibreOfficeDictionary('main', 60, $headers);

        expect($source)->toBeInstanceOf(DictionarySourceInterface::class);
        // Headers are configured in Transport, not stored in metadata
    });

    describe('specific dictionary constructors', function () {
        it('creates LibreOffice Thai dictionary', function () {
            $source = DictionarySourceFactory::createLibreOfficeThaiDictionary();

            expect($source)->toBeInstanceOf(DictionarySourceInterface::class);

            $metadata = $source->getMetadata();
            expect($metadata['url'])->toBe(DictionaryUrls::LIBREOFFICE_THAI_MAIN);
        });

        it('creates LibreOffice typos transliteration dictionary', function () {
            $source = DictionarySourceFactory::createLibreOfficeTyposTranslitDictionary();

            expect($source)->toBeInstanceOf(DictionarySourceInterface::class);

            $metadata = $source->getMetadata();
            expect($metadata['url'])->toBe(DictionaryUrls::LIBREOFFICE_THAI_TYPOS_TRANSLIT);
        });

        it('creates LibreOffice typos common dictionary', function () {
            $source = DictionarySourceFactory::createLibreOfficeTyposCommonDictionary();

            expect($source)->toBeInstanceOf(DictionarySourceInterface::class);

            $metadata = $source->getMetadata();
            expect($metadata['url'])->toBe(DictionaryUrls::LIBREOFFICE_THAI_TYPOS_COMMON);
        });

        it('accepts timeout parameter in specific constructors', function () {
            $source = DictionarySourceFactory::createLibreOfficeThaiDictionary(60);

            expect($source)->toBeInstanceOf(DictionarySourceInterface::class);
        });

        it('accepts headers parameter in specific constructors', function () {
            $headers = ['Custom-Header' => 'Value'];
            $source = DictionarySourceFactory::createLibreOfficeThaiDictionary(30, $headers);

            expect($source)->toBeInstanceOf(DictionarySourceInterface::class);
            // Headers are configured in Transport, not stored in metadata
        });
    });

    describe('createFromFile', function () {
        it('creates source from file path', function () {
            $filePath = __DIR__.'/../../../../resources/dictionaries/sample.txt';

            $source = DictionarySourceFactory::createFromFile($filePath);

            expect($source)->toBeInstanceOf(DictionarySourceInterface::class);

            $metadata = $source->getMetadata();
            expect($metadata['file_path'])->toBe($filePath);
            expect($metadata['parser_type'])->toBe('plain_text');
        });

        it('accepts parser type parameter', function () {
            $filePath = __DIR__.'/../../../../resources/dictionaries/sample.txt';

            $source = DictionarySourceFactory::createFromFile($filePath, 'main');

            expect($source)->toBeInstanceOf(DictionarySourceInterface::class);

            $metadata = $source->getMetadata();
            expect($metadata['parser_type'])->toBe('libreoffice_main');
        });

        it('throws exception for unknown parser type', function () {
            $filePath = __DIR__.'/../../../../resources/dictionaries/sample.txt';

            expect(fn () => DictionarySourceFactory::createFromFile($filePath, 'unknown'))
                ->toThrow(InvalidArgumentException::class, 'Unknown parser type: unknown');
        });
    });

    describe('createFromUrl', function () {
        it('creates source from URL', function () {
            $url = 'https://example.com/dictionary.txt';

            $source = DictionarySourceFactory::createFromUrl($url);

            expect($source)->toBeInstanceOf(DictionarySourceInterface::class);

            $metadata = $source->getMetadata();
            expect($metadata['url'])->toBe($url);
        });

        it('accepts parser type parameter', function () {
            $url = 'https://example.com/dictionary.txt';

            $source = DictionarySourceFactory::createFromUrl($url, 'plain');

            expect($source)->toBeInstanceOf(DictionarySourceInterface::class);

            $metadata = $source->getMetadata();
            expect($metadata['parser_type'])->toBe('plain_text');
        });

        it('accepts headers as third parameter (array)', function () {
            $url = 'https://example.com/dictionary.txt';
            $headers = ['Authorization' => 'Bearer token'];

            $source = DictionarySourceFactory::createFromUrl($url, 'main', 30, $headers);

            expect($source)->toBeInstanceOf(DictionarySourceInterface::class);
            // Headers are configured in Transport, not stored in metadata
        });

        it('accepts timeout as third parameter for backward compatibility', function () {
            $url = 'https://example.com/dictionary.txt';

            $source = DictionarySourceFactory::createFromUrl($url, 'main', 60);

            expect($source)->toBeInstanceOf(DictionarySourceInterface::class);
        });

        it('accepts both timeout and headers', function () {
            $url = 'https://example.com/dictionary.txt';
            $headers = ['Custom' => 'Header'];

            $source = DictionarySourceFactory::createFromUrl($url, 'main', 60, $headers);

            expect($source)->toBeInstanceOf(DictionarySourceInterface::class);
            // Headers are configured in Transport, not stored in metadata
        });
    });

    describe('getAllLibreOfficeDictionaries', function () {
        it('returns all LibreOffice dictionaries', function () {
            $dictionaries = DictionarySourceFactory::getAllLibreOfficeDictionaries();

            expect($dictionaries)
                ->toBeArray()
                ->toHaveCount(3)
                ->toHaveKeys(['main', 'typos_translit', 'typos_common']);

            foreach ($dictionaries as $source) {
                expect($source)->toBeInstanceOf(DictionarySourceInterface::class);
            }
        });

        it('accepts timeout parameter', function () {
            $dictionaries = DictionarySourceFactory::getAllLibreOfficeDictionaries(60);

            expect($dictionaries)->toBeArray()->toHaveCount(3);
        });

        it('accepts headers parameter', function () {
            $headers = ['User-Agent' => 'Test'];
            $dictionaries = DictionarySourceFactory::getAllLibreOfficeDictionaries(30, $headers);

            expect($dictionaries)->toBeArray()->toHaveCount(3);
            // Headers are configured in Transport, not stored in metadata
        });
    });

    describe('create method integration', function () {
        it('creates file source via create method', function () {
            $filePath = __DIR__.'/../../../../resources/dictionaries/sample.txt';

            $source = DictionarySourceFactory::create('file', $filePath);

            expect($source)->toBeInstanceOf(DictionarySourceInterface::class);
        });

        it('passes options to file source', function () {
            $filePath = __DIR__.'/../../../../resources/dictionaries/sample.txt';

            $source = DictionarySourceFactory::create('file', $filePath, [
                'parser_type' => 'main',
            ]);

            expect($source)->toBeInstanceOf(DictionarySourceInterface::class);

            $metadata = $source->getMetadata();
            expect($metadata['parser_type'])->toBe('libreoffice_main');
        });

        it('passes options to URL source', function () {
            $url = 'https://example.com/dictionary.txt';

            $source = DictionarySourceFactory::create('url', $url, [
                'parser_type' => 'plain',
                'timeout' => 60,
                'headers' => ['Custom' => 'Header'],
            ]);

            expect($source)->toBeInstanceOf(DictionarySourceInterface::class);
            // Headers are configured in Transport, not stored in metadata
        });

        it('creates libreoffice sources via create method', function () {
            $types = ['libreoffice_main', 'libreoffice_typos_translit', 'libreoffice_typos_common'];

            foreach ($types as $type) {
                $source = DictionarySourceFactory::create($type);

                expect($source)->toBeInstanceOf(DictionarySourceInterface::class);
            }
        });
    });
});
