<?php

namespace JeffersonGoncalves\HtmlSanitizer;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer as SymfonyHtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * Sanitises rendered HTML that originated from untrusted sources — such as
 * GitHub READMEs of third-party repos and the markdown body of imported
 * articles. Both typically render with raw-HTML enabled (CommonMark
 * `html_input: allow` / Str::markdown), so a crafted source could ship
 * `<script>` or an inline event handler (`<img onerror=...>`); when a site's
 * CSP keeps `'unsafe-inline'` for Alpine, such handlers would otherwise
 * execute.
 *
 * Drops scripts, styles and every event-handler attribute while keeping the
 * presentational subset a README/article needs (headings, lists, tables,
 * code blocks, images, links). Your own post-processing (target/rel, lazy
 * loading, table wrappers) should run AFTER this and re-add those safe
 * attributes.
 *
 * Behaviour is driven by the published `config/html-sanitizer.php` file
 * (allowed schemes, allowed attributes, max input length).
 */
class HtmlSanitizer
{
    /**
     * Generous default cap (~2 MB) for input length. Large enough for any real
     * README/article, small enough to bound work on a hostile payload. The
     * published config can override this (use -1 for unlimited).
     */
    private const DEFAULT_MAX_INPUT_LENGTH = 2_000_000;

    /**
     * Built sanitizers keyed by a hash of the config that produced them.
     * Building a HtmlSanitizerConfig (allowlist, schemes, attributes) on every
     * clean() call is wasteful; reuse the instance while the config is stable,
     * and rebuild automatically when any relevant config value changes (e.g.
     * in tests that override a scheme between calls).
     *
     * @var array<string, SymfonyHtmlSanitizer>
     */
    private static array $sanitizers = [];

    public static function clean(string $html): string
    {
        return self::sanitizer()->sanitize($html);
    }

    /**
     * Clear the memoised sanitizers. Mainly useful in tests, or after a
     * runtime config change that should take effect immediately.
     */
    public static function flush(): void
    {
        self::$sanitizers = [];
    }

    private static function sanitizer(): SymfonyHtmlSanitizer
    {
        $linkSchemes = self::schemes('link_schemes', ['https', 'http', 'mailto']);
        $mediaSchemes = self::schemes('media_schemes', ['https', 'http']);
        $maxInputLength = self::maxInputLength();
        $allowRelativeLinks = self::flag('allow_relative_links', true);
        $allowRelativeMedias = self::flag('allow_relative_medias', true);
        $attributes = self::attributes();

        $signature = md5(serialize([
            $linkSchemes,
            $mediaSchemes,
            $maxInputLength,
            $allowRelativeLinks,
            $allowRelativeMedias,
            $attributes,
        ]));

        return self::$sanitizers[$signature] ??= self::build(
            $linkSchemes,
            $mediaSchemes,
            $maxInputLength,
            $allowRelativeLinks,
            $allowRelativeMedias,
            $attributes,
        );
    }

    /**
     * @param  array<int, string>  $linkSchemes
     * @param  array<int, string>  $mediaSchemes
     * @param  array<string, string|array<int, string>>  $attributes
     */
    private static function build(
        array $linkSchemes,
        array $mediaSchemes,
        int $maxInputLength,
        bool $allowRelativeLinks,
        bool $allowRelativeMedias,
        array $attributes,
    ): SymfonyHtmlSanitizer {
        $config = (new HtmlSanitizerConfig)
            ->allowSafeElements()
            ->allowLinkSchemes($linkSchemes)
            ->allowMediaSchemes($mediaSchemes)
            // Symfony defaults this to 20000 bytes and silently truncates longer
            // input mid-content. We raise it to a generous default so real
            // READMEs/articles survive, while still bounding memory/CPU on a
            // hostile multi-megabyte payload (set -1 to disable entirely).
            ->withMaxInputLength($maxInputLength);

        if ($allowRelativeLinks) {
            $config = $config->allowRelativeLinks();
        }

        if ($allowRelativeMedias) {
            $config = $config->allowRelativeMedias();
        }

        foreach ($attributes as $name => $elements) {
            $config = $config->allowAttribute($name, $elements);
        }

        return new SymfonyHtmlSanitizer($config);
    }

    private static function maxInputLength(): int
    {
        $value = self::config('max_input_length', self::DEFAULT_MAX_INPUT_LENGTH);

        return is_numeric($value) ? (int) $value : self::DEFAULT_MAX_INPUT_LENGTH;
    }

    private static function flag(string $key, bool $default): bool
    {
        $value = self::config($key, $default);

        return is_bool($value) ? $value : $default;
    }

    /**
     * @param  array<int, string>  $default
     * @return array<int, string>
     */
    private static function schemes(string $key, array $default): array
    {
        $value = self::config($key, $default);

        if (! is_array($value)) {
            return $default;
        }

        return array_values(array_map('strval', $value));
    }

    /**
     * @return array<string, string|array<int, string>>
     */
    private static function attributes(): array
    {
        $value = self::config('attributes', []);

        if (! is_array($value)) {
            return [];
        }

        $attributes = [];

        foreach ($value as $name => $elements) {
            if (! is_string($name)) {
                continue;
            }

            if (is_string($elements)) {
                $attributes[$name] = $elements;
            } elseif (is_array($elements)) {
                $attributes[$name] = array_values(array_map('strval', $elements));
            }
        }

        return $attributes;
    }

    private static function config(string $key, mixed $default): mixed
    {
        if (! function_exists('config')) {
            return $default;
        }

        return config('html-sanitizer.'.$key, $default);
    }
}
