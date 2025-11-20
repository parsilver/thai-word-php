<?php

use Farzai\ThaiWord\Dictionary\Config\DictionaryUrls;

describe('DictionaryUrls', function () {

    it('provides LibreOffice Thai main dictionary URL', function () {
        $url = DictionaryUrls::getLibreOfficeUrl('main');

        expect($url)->toBe('https://raw.githubusercontent.com/LibreOffice/dictionaries/master/th_TH/th_TH.dic');
        expect($url)->toBeValidUrl();
    });

    it('provides LibreOffice Thai typos transliteration dictionary URL', function () {
        $url = DictionaryUrls::getLibreOfficeUrl('typos_translit');

        expect($url)->toBe('https://raw.githubusercontent.com/LibreOffice/dictionaries/master/th_TH/typos-translit.txt');
        expect($url)->toBeValidUrl();
    });

    it('provides LibreOffice Thai typos common dictionary URL', function () {
        $url = DictionaryUrls::getLibreOfficeUrl('typos_common');

        expect($url)->toBe('https://raw.githubusercontent.com/LibreOffice/dictionaries/master/th_TH/typos-common.txt');
        expect($url)->toBeValidUrl();
    });

    it('throws exception for unknown dictionary type', function () {
        expect(fn () => DictionaryUrls::getLibreOfficeUrl('unknown'))
            ->toThrow(InvalidArgumentException::class, 'Unknown LibreOffice dictionary type: unknown');
    });

    it('returns all LibreOffice URLs', function () {
        $urls = DictionaryUrls::getLibreOfficeUrls();

        expect($urls)->toBeArray()
            ->toHaveCount(3)
            ->toHaveKey('main')
            ->toHaveKey('typos_translit')
            ->toHaveKey('typos_common');

        foreach ($urls as $url) {
            expect($url)->toBeValidUrl();
        }
    });

    it('validates known LibreOffice URLs correctly', function () {
        $mainUrl = DictionaryUrls::LIBREOFFICE_THAI_MAIN;
        $typosTranslitUrl = DictionaryUrls::LIBREOFFICE_THAI_TYPOS_TRANSLIT;
        $typosCommonUrl = DictionaryUrls::LIBREOFFICE_THAI_TYPOS_COMMON;

        expect(DictionaryUrls::isLibreOfficeUrl($mainUrl))->toBeTrue();
        expect(DictionaryUrls::isLibreOfficeUrl($typosTranslitUrl))->toBeTrue();
        expect(DictionaryUrls::isLibreOfficeUrl($typosCommonUrl))->toBeTrue();
        expect(DictionaryUrls::isLibreOfficeUrl('https://example.com'))->toBeFalse();
    });

    it('has constants matching getLibreOfficeUrl method', function () {
        expect(DictionaryUrls::LIBREOFFICE_THAI_MAIN)
            ->toBe(DictionaryUrls::getLibreOfficeUrl('main'));

        expect(DictionaryUrls::LIBREOFFICE_THAI_TYPOS_TRANSLIT)
            ->toBe(DictionaryUrls::getLibreOfficeUrl('typos_translit'));

        expect(DictionaryUrls::LIBREOFFICE_THAI_TYPOS_COMMON)
            ->toBe(DictionaryUrls::getLibreOfficeUrl('typos_common'));
    });
});

// Custom expectation for URL validation
expect()->extend('toBeValidUrl', function () {
    return $this->toBeString()
        ->toMatch('/^https?:\/\/.+/')
        ->and(filter_var($this->value, FILTER_VALIDATE_URL))
        ->not->toBeFalse();
});
