---
name: html-sanitizer-development
description: Development guide for laravel-html-sanitizer, a thin wrapper around the Symfony HTML Sanitizer that safely cleans untrusted HTML by stripping scripts, event handlers and Alpine attributes while keeping a presentational allowlist.
---

# HTML Sanitizer Development Skill

## When to use this skill

- When developing or extending the laravel-html-sanitizer package
- When adjusting the allowlist (safe elements, schemes, attributes)
- When writing tests for sanitization behaviour
- When debugging why a tag or attribute was kept or stripped

## Setup

### Requirements
- PHP 8.2+
- Laravel 11, 12, or 13
- `spatie/laravel-package-tools` ^1.14
- `symfony/html-sanitizer` ^7.0|^8.0

### Installation

```bash
composer require jeffersongoncalves/laravel-html-sanitizer
```

The package ships a config file with safe defaults. Publish it only when you
need to adjust the allowlist:

```bash
php artisan vendor:publish --tag="html-sanitizer-config"
```

## Package Structure

```
config/
  html-sanitizer.php                 # Allowed schemes/attributes, relative-link flags, max input length
src/
  HtmlSanitizerServiceProvider.php   # Spatie PackageServiceProvider; registers the config file
  HtmlSanitizer.php                  # Static clean() API + memoised sanitizer() factory (config-driven)
```

## Features

### Static `clean()` API

```php
use JeffersonGoncalves\HtmlSanitizer\HtmlSanitizer;

$clean = HtmlSanitizer::clean($untrustedHtml);
```

`clean()` builds a `Symfony\Component\HtmlSanitizer\HtmlSanitizer` from a `HtmlSanitizerConfig` and runs `->sanitize()` on the input. The built sanitizer is memoised per config signature, so repeated calls reuse it; it rebuilds automatically when any config value changes (call `HtmlSanitizer::flush()` to force a rebuild).

### The allowlist

`HtmlSanitizer::sanitizer()` builds the config entirely from `config/html-sanitizer.php`. With the default config that is equivalent to:

```php
(new HtmlSanitizerConfig)
    ->allowSafeElements()
    ->allowRelativeLinks()       // config: allow_relative_links (default true)
    ->allowRelativeMedias()      // config: allow_relative_medias (default true)
    ->allowLinkSchemes(['https', 'http', 'mailto'])   // config: link_schemes
    ->allowMediaSchemes(['https', 'http'])            // config: media_schemes — NO 'data'
    ->withMaxInputLength(2_000_000)                   // config: max_input_length (~2 MB cap)
    ->allowAttribute('class', '*')                    // config: attributes
    ->allowAttribute('id', '*')
    ->allowAttribute('width', ['img'])
    ->allowAttribute('height', ['img']);
```

- `allowSafeElements()` enables the curated presentational set (headings, lists, tables, code blocks, images, links, ...).
- `class`/`id` are kept on every element so heading permalinks, code-language hints and table wrappers keep their styling hooks.
- `width`/`height` are scoped to `<img>` (dimension values, not arbitrary CSS) so README image galleries flow correctly.
- `data` is **deliberately excluded** from media schemes: a `data:image/svg+xml` URI can carry executable script. Only add it if you fully trust the source.
- `max_input_length` defaults to ~2 MB to bound work on a hostile payload; set `-1` to disable the cap.
- Everything not allowed — `<script>`, `<style>`, event-handler attributes (`onerror`, `onclick`), Alpine `x-*` attributes, unsafe schemes — is removed.

## Why this exists

Rendered HTML from untrusted sources (third-party GitHub READMEs, imported article bodies) often renders with raw HTML enabled (CommonMark `html_input: allow` / `Str::markdown`). A crafted source could ship `<script>` or an inline event handler; when the host site's CSP keeps `'unsafe-inline'` for Alpine, such handlers would otherwise execute. Sanitizing is the sole XSS control on those routes.

Run any post-processing that re-adds safe attributes (target/rel, lazy loading, table wrappers) AFTER `clean()`.

## Testing Patterns

### Asserting stripped content

```php
use JeffersonGoncalves\HtmlSanitizer\HtmlSanitizer;

it('strips script tags', function () {
    $clean = HtmlSanitizer::clean('<p>Hi</p><script>alert(1)</script>');

    expect($clean)
        ->not->toContain('<script')
        ->toContain('<p>Hi</p>');
});

it('strips event handlers', function () {
    expect(HtmlSanitizer::clean('<img src="x" onerror="x()">'))
        ->not->toContain('onerror');
});

it('strips alpine attributes', function () {
    expect(HtmlSanitizer::clean('<div x-data="{}">Menu</div>'))
        ->not->toContain('x-data');
});
```

### Asserting kept content

```php
it('keeps tables, links and image sizing', function () {
    expect(HtmlSanitizer::clean('<table><tr><td>1</td></tr></table>'))->toContain('<td>1</td>');
    expect(HtmlSanitizer::clean('<a href="https://example.com">L</a>'))->toContain('href="https://example.com"');
    expect(HtmlSanitizer::clean('<img src="https://e.com/a.png" width="20%">'))->toContain('width="20%"');
    expect(HtmlSanitizer::clean('<p class="lead" id="x">Hi</p>'))->toContain('class="lead"');
});
```

### Running Tests

```bash
# Run all tests
vendor/bin/pest

# Run with coverage
vendor/bin/pest --coverage

# Static analysis
vendor/bin/phpstan analyse

# Code formatting
vendor/bin/pint
```

## Widening the allowlist

The allowlist is config-driven — publish the config and edit `config/html-sanitizer.php` rather than the source:

```php
// Allow an attribute on specific elements only (preferred — keep it scoped)
'attributes' => [
    // ...defaults...
    'data-foo' => ['div', 'span'],
],

// Allow an additional link scheme
'link_schemes' => ['https', 'http', 'mailto', 'tel'],
```

**Never** add `data` to `media_schemes` unless you fully trust the source — `data:image/svg+xml` URIs can execute script.

Keep additions narrow — this class is the sole XSS control on the routes that use it. Add a test covering both the newly-kept case and a related case that must still be stripped.
