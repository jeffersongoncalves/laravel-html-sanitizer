<div class="filament-hidden">

![Laravel HTML Sanitizer](https://raw.githubusercontent.com/jeffersongoncalves/laravel-html-sanitizer/master/art/jeffersongoncalves-laravel-html-sanitizer.png)

</div>

# Laravel HTML Sanitizer

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jeffersongoncalves/laravel-html-sanitizer.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/laravel-html-sanitizer)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/jeffersongoncalves/laravel-html-sanitizer/run-tests.yml?branch=master&label=tests&style=flat-square)](https://github.com/jeffersongoncalves/laravel-html-sanitizer/actions?query=workflow%3Arun-tests+branch%3Amaster)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/jeffersongoncalves/laravel-html-sanitizer/fix-php-code-style-issues.yml?branch=master&label=code%20style&style=flat-square)](https://github.com/jeffersongoncalves/laravel-html-sanitizer/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/jeffersongoncalves/laravel-html-sanitizer.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/laravel-html-sanitizer)

This Laravel package provides a simple wrapper around the Symfony HTML Sanitizer to safely clean untrusted HTML. It strips scripts, inline event handlers, and Alpine attributes while keeping the presentational subset (headings, lists, tables, code blocks, images, links) that rendered Markdown and READMEs need. The package is easy to install and configure, seamlessly integrating with your existing Laravel application.

## Installation

You can install the package via composer:

```bash
composer require jeffersongoncalves/laravel-html-sanitizer
```

## Usage

Pass any untrusted HTML through `HtmlSanitizer::clean()` before rendering it:

```php
use JeffersonGoncalves\HtmlSanitizer\HtmlSanitizer;

$dirty = '<p>Hello</p><script>alert("xss")</script><img src="x" onerror="steal()">';

$clean = HtmlSanitizer::clean($dirty);
// <p>Hello</p><img src="x">
```

The sanitizer:

- drops `<script>`, `<style>` and every event-handler attribute (`onerror`, `onclick`, ...);
- strips Alpine `x-*` attributes;
- keeps the safe presentational subset: headings, lists, tables, code blocks, images and links;
- allows relative links/medias and the `https`, `http`, `mailto` link schemes (plus `data` for media);
- preserves `class`/`id` attributes (for heading permalinks, code-language hints and table wrappers) and `width`/`height` on `<img>`.

It is intended for rendered HTML that originated from untrusted sources — GitHub READMEs of third-party repos and the Markdown body of imported articles — where raw HTML is enabled during rendering.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Jèfferson Gonçalves](https://github.com/jeffersongoncalves)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
