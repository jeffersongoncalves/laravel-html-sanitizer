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
    public static function clean(string $html): string
    {
        return self::sanitizer()->sanitize($html);
    }

    private static function sanitizer(): SymfonyHtmlSanitizer
    {
        $config = (new HtmlSanitizerConfig)
            ->allowSafeElements()
            ->allowLinkSchemes(self::schemes('link_schemes', ['https', 'http', 'mailto']))
            ->allowMediaSchemes(self::schemes('media_schemes', ['https', 'http']))
            // Symfony defaults this to 20000 bytes and silently truncates longer
            // input mid-content; -1 disables the cap so long READMEs/articles
            // survive intact.
            ->withMaxInputLength(self::maxInputLength());

        if (self::flag('allow_relative_links', true)) {
            $config = $config->allowRelativeLinks();
        }

        if (self::flag('allow_relative_medias', true)) {
            $config = $config->allowRelativeMedias();
        }

        foreach (self::attributes() as $name => $elements) {
            $config = $config->allowAttribute($name, $elements);
        }

        return new SymfonyHtmlSanitizer($config);
    }

    private static function maxInputLength(): int
    {
        $value = self::config('max_input_length', -1);

        return is_numeric($value) ? (int) $value : -1;
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
