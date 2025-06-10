<?php

declare(strict_types=1);

namespace Farzai\ThaiWord\Dictionary\Config;

/**
 * Centralized configuration for dictionary URLs
 *
 * This class provides a single source of truth for all dictionary URLs,
 * eliminating duplication and making it easier to maintain and update URLs.
 */
final class DictionaryUrls
{
    /**
     * LibreOffice Thai dictionary URLs
     */
    public const LIBREOFFICE_THAI_MAIN = 'https://cgit.freedesktop.org/libreoffice/dictionaries/plain/th_TH/th_TH.dic';

    public const LIBREOFFICE_THAI_TYPOS_TRANSLIT = 'https://cgit.freedesktop.org/libreoffice/dictionaries/plain/th_TH/typos-translit.txt';

    public const LIBREOFFICE_THAI_TYPOS_COMMON = 'https://cgit.freedesktop.org/libreoffice/dictionaries/plain/th_TH/typos-common.txt';

    /**
     * Get all LibreOffice dictionary URLs
     *
     * @return array<string, string> Array of dictionary type => URL mappings
     */
    public static function getLibreOfficeUrls(): array
    {
        return [
            'main' => self::LIBREOFFICE_THAI_MAIN,
            'typos_translit' => self::LIBREOFFICE_THAI_TYPOS_TRANSLIT,
            'typos_common' => self::LIBREOFFICE_THAI_TYPOS_COMMON,
        ];
    }

    /**
     * Get URL for a specific LibreOffice dictionary type
     *
     * @param  string  $type  Dictionary type (main, typos_translit, typos_common)
     * @return string URL for the dictionary type
     *
     * @throws \InvalidArgumentException If dictionary type is unknown
     */
    public static function getLibreOfficeUrl(string $type): string
    {
        return match ($type) {
            'main' => self::LIBREOFFICE_THAI_MAIN,
            'typos_translit' => self::LIBREOFFICE_THAI_TYPOS_TRANSLIT,
            'typos_common' => self::LIBREOFFICE_THAI_TYPOS_COMMON,
            default => throw new \InvalidArgumentException("Unknown LibreOffice dictionary type: {$type}")
        };
    }

    /**
     * Validate if a URL is a known LibreOffice dictionary URL
     *
     * @param  string  $url  URL to validate
     * @return bool True if URL is a known LibreOffice dictionary URL
     */
    public static function isLibreOfficeUrl(string $url): bool
    {
        return in_array($url, self::getLibreOfficeUrls(), true);
    }
}
