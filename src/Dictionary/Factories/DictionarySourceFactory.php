<?php

declare(strict_types=1);

namespace Farzai\ThaiWord\Dictionary\Factories;

use Farzai\ThaiWord\Contracts\DictionarySourceInterface;
use Farzai\ThaiWord\Dictionary\Config\DictionaryUrls;
use Farzai\ThaiWord\Dictionary\Parsers\LibreOfficeParser;
use Farzai\ThaiWord\Dictionary\Parsers\PlainTextParser;
use Farzai\ThaiWord\Dictionary\Sources\FileDictionarySource;
use Farzai\ThaiWord\Dictionary\Sources\RemoteDictionarySource;
use Farzai\ThaiWord\Http\HttpClientFactory;
use InvalidArgumentException;

/**
 * Factory for creating dictionary sources
 *
 * Provides convenient methods for creating different types of dictionary sources
 * with appropriate parsers and configurations. Uses centralized URL configuration
 * to eliminate duplication and improve maintainability.
 */
class DictionarySourceFactory
{
    /**
     * Create a LibreOffice dictionary source by type
     *
     * @param  string  $type  Dictionary type (main, typos_translit, typos_common)
     * @param  int  $timeout  Connection timeout in seconds
     * @param  array<string, string>  $headers  Additional HTTP headers
     *
     * @throws InvalidArgumentException If dictionary type is unknown
     */
    public static function createLibreOfficeDictionary(string $type, int $timeout = 30, array $headers = []): DictionarySourceInterface
    {
        return self::createFromUrl(
            DictionaryUrls::getLibreOfficeUrl($type),
            $type,
            $timeout,
            $headers
        );
    }

    /**
     * Create a LibreOffice Thai main dictionary source
     *
     * @param  int  $timeout  Connection timeout in seconds
     * @param  array<string, string>  $headers  Additional HTTP headers
     */
    public static function createLibreOfficeThaiDictionary(int $timeout = 30, array $headers = []): DictionarySourceInterface
    {
        return self::createLibreOfficeDictionary('main', $timeout, $headers);
    }

    /**
     * Create a LibreOffice Thai typos transliteration dictionary source
     *
     * @param  int  $timeout  Connection timeout in seconds
     * @param  array<string, string>  $headers  Additional HTTP headers
     */
    public static function createLibreOfficeTyposTranslitDictionary(int $timeout = 30, array $headers = []): DictionarySourceInterface
    {
        return self::createLibreOfficeDictionary('typos_translit', $timeout, $headers);
    }

    /**
     * Create a LibreOffice Thai common typos dictionary source
     *
     * @param  int  $timeout  Connection timeout in seconds
     * @param  array<string, string>  $headers  Additional HTTP headers
     */
    public static function createLibreOfficeTyposCommonDictionary(int $timeout = 30, array $headers = []): DictionarySourceInterface
    {
        return self::createLibreOfficeDictionary('typos_common', $timeout, $headers);
    }

    /**
     * Create a file dictionary source from local file
     *
     * @param  string  $filePath  Dictionary file path
     * @param  string  $parserType  Parser type ('plain', 'main', 'typos_translit', 'typos_common')
     *
     * @throws InvalidArgumentException If parser type is unknown
     */
    public static function createFromFile(
        string $filePath,
        string $parserType = 'plain'
    ): DictionarySourceInterface {
        $parser = match ($parserType) {
            'plain' => new PlainTextParser,
            LibreOfficeParser::TYPE_MAIN => new LibreOfficeParser(LibreOfficeParser::TYPE_MAIN),
            LibreOfficeParser::TYPE_TYPOS_TRANSLIT => new LibreOfficeParser(LibreOfficeParser::TYPE_TYPOS_TRANSLIT),
            LibreOfficeParser::TYPE_TYPOS_COMMON => new LibreOfficeParser(LibreOfficeParser::TYPE_TYPOS_COMMON),
            default => throw new InvalidArgumentException("Unknown parser type: {$parserType}")
        };

        return new FileDictionarySource($filePath, $parser);
    }

    /**
     * Create a remote dictionary source from URL
     *
     * @param  string  $url  Dictionary URL
     * @param  string  $parserType  Parser type ('main', 'typos_translit', 'typos_common')
     * @param  int  $timeout  Connection timeout in seconds
     * @param  array<string, string>  $headers  Additional HTTP headers
     *
     * @throws InvalidArgumentException If parser type is unknown or HTTP client unavailable
     */
    public static function createFromUrl(
        string $url,
        string $parserType = LibreOfficeParser::TYPE_MAIN,
        int $timeout = 30,
        array $headers = []
    ): DictionarySourceInterface {
        $parser = match ($parserType) {
            'plain' => new PlainTextParser,
            LibreOfficeParser::TYPE_MAIN => new LibreOfficeParser(LibreOfficeParser::TYPE_MAIN),
            LibreOfficeParser::TYPE_TYPOS_TRANSLIT => new LibreOfficeParser(LibreOfficeParser::TYPE_TYPOS_TRANSLIT),
            LibreOfficeParser::TYPE_TYPOS_COMMON => new LibreOfficeParser(LibreOfficeParser::TYPE_TYPOS_COMMON),
            default => throw new InvalidArgumentException("Unknown parser type: {$parserType}")
        };

        try {
            // Create HTTP client using factory (will throw if dependencies missing)
            $httpClient = HttpClientFactory::create($timeout);

            return new RemoteDictionarySource(
                $url,
                $httpClient,
                $parser,
                $headers
            );
        } catch (\Throwable $e) {
            throw new InvalidArgumentException(
                "Failed to create remote dictionary source: {$e->getMessage()}",
                0,
                $e
            );
        }
    }

    /**
     * Get all available LibreOffice dictionary sources
     *
     * @param  int  $timeout  Connection timeout in seconds
     * @param  array<string, string>  $headers  Additional HTTP headers
     * @return array<string, DictionarySourceInterface>
     */
    public static function getAllLibreOfficeDictionaries(int $timeout = 30, array $headers = []): array
    {
        return [
            'main' => self::createLibreOfficeThaiDictionary($timeout, $headers),
            'typos_translit' => self::createLibreOfficeTyposTranslitDictionary($timeout, $headers),
            'typos_common' => self::createLibreOfficeTyposCommonDictionary($timeout, $headers),
        ];
    }

    /**
     * Generic create method for dictionary sources
     *
     * @param  string  $type  Source type ('file', 'url', 'libreoffice_main', 'libreoffice_typos_translit', 'libreoffice_typos_common')
     * @param  string  $source  Source path/URL (ignored for libreoffice types)
     * @param  array<string, mixed>  $options  Additional options
     *
     * @throws InvalidArgumentException If type is unknown
     */
    public static function create(string $type, string $source = '', array $options = []): DictionarySourceInterface
    {
        $timeout = $options['timeout'] ?? 30;
        $headers = $options['headers'] ?? [];

        return match ($type) {
            'file' => self::createFromFile($source, $options['parser_type'] ?? 'plain'),
            'url' => self::createFromUrl($source, $options['parser_type'] ?? LibreOfficeParser::TYPE_MAIN, $timeout, $headers),
            'libreoffice_main' => self::createLibreOfficeThaiDictionary($timeout, $headers),
            'libreoffice_typos_translit' => self::createLibreOfficeTyposTranslitDictionary($timeout, $headers),
            'libreoffice_typos_common' => self::createLibreOfficeTyposCommonDictionary($timeout, $headers),
            // Backward compatibility
            'libreoffice' => self::createLibreOfficeThaiDictionary($timeout, $headers),
            default => throw new InvalidArgumentException("Unknown dictionary source type: {$type}")
        };
    }
}
