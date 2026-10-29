# Changelog

All notable changes to `thai-word` will be documented in this file.

## 1.2.0 - 2026-10-29

### Fixed

- Texts longer than 1000 characters no longer come out with duplicated characters. Segmentation now runs in one linear pass with no chunking: 200k characters take about 0.3 s.
- Unknown Thai text is emitted one character cluster at a time, so vowels and tone marks such as `่` or `์` never appear as tokens on their own. A dictionary match can no longer end before a vowel or mark that belongs to the previous character.
- Runs of Thai digits stay together (`๒๕๖๙`).
- Hunspell morphological fields (` ph:...`) are stripped when a dictionary is loaded. Before, those lines were dropped entirely, losing 515 words from the bundled dictionary.
- `HashDictionary::remove()` now recomputes the maximum word length.

### Added

- `ThaiSegmenter::isUsingFallbackDictionary()` tells you when the segmenter is running on the small built-in word list because no real dictionary could be loaded.
- The CLI supports symfony/console 8 and still works with 7.x. CI now runs on PHP 8.5.

### Behaviour changes

- Unknown Thai text is returned as cluster tokens instead of single code points.
- A run of Thai digits is returned as one token.
- A period after a Latin word or number is now its own token: `example.com.` gives `example.com`, `.`, and `e.g.` gives `e.g`, `.`.

Full details: #20

## 1.1.0 - 2025-06-11

### What's Changed

* Enhance Thai Word Segmentation with Suggestions Feature by @parsilver in https://github.com/parsilver/thai-word-php/pull/1

### New Contributors

* @parsilver made their first contribution in https://github.com/parsilver/thai-word-php/pull/1

**Full Changelog**: https://github.com/parsilver/thai-word-php/compare/1.0.0...1.1.0
