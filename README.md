# Thai Word Segmentation - PHP Library

[![Latest Version on Packagist](https://img.shields.io/packagist/v/farzai/thai-word.svg?style=flat-square)](https://packagist.org/packages/farzai/thai-word)
[![Tests](https://img.shields.io/github/actions/workflow/status/parsilver/thai-word-php/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/parsilver/thai-word-php/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/farzai/thai-word.svg?style=flat-square)](https://packagist.org/packages/farzai/thai-word)


## Installation

You can install the package via composer:

```bash
composer require farzai/thai-word
```

## Basic Usage

```php
use Farzai\ThaiWord\Segmenter\ThaiSegmenter;

$segmenter = new ThaiSegmenter();
$words = $segmenter->segment('สวัสดีครับผมชื่อสมชาย');

// Result: ['สวัสดี', 'ครับ', 'ผม', 'ชื่อ', 'สมชาย']
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/parsilver/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [parsilver](https://github.com/parsilver)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
