# Changelog

All notable changes to `laravel-html-sanitizer` will be documented in this file.

## v1.3.0 - 2026-10-28

- Memoize the sanitizer per config.
- `max_input_length` now defaults to 2,000,000 bytes instead of unlimited. Set it to `-1` to keep the old behaviour.

## v1.2.0 - 2026-06-21

**Full Changelog**: https://github.com/jeffersongoncalves/laravel-html-sanitizer/compare/v1.1.1...v1.2.0

## v1.1.1 - 2026-06-20

chore: ignore the .phpunit.cache directory.

## v1.1.0 - 2026-06-20

Add support for symfony/html-sanitizer ^8.0 (alongside ^7.0).

## v1.0.1 - 2026-06-20

Allow symfony/html-sanitizer ^8.0 (in addition to ^7.0).

## v1.0.0 - 2026-06-20

Initial release.

## Unreleased

- Initial release.
