## Laravel HTML Sanitizer

### Overview
Laravel HTML Sanitizer is a thin wrapper around the Symfony HTML Sanitizer that safely cleans untrusted HTML. It drops scripts, inline event handlers and Alpine attributes while keeping the presentational subset (headings, lists, tables, code blocks, images, links) that rendered Markdown and READMEs need.

**Namespace:** `JeffersonGoncalves\HtmlSanitizer`
**Service Provider:** `HtmlSanitizerServiceProvider` (auto-discovered)

### Key Concepts
- **Static API:** Call `HtmlSanitizer::clean($html)` — there is no facade, but there is a config file (`config/html-sanitizer.php`) with safe defaults.
- **Allowlist-based:** Built on Symfony's `HtmlSanitizerConfig` with `allowSafeElements()`; anything not explicitly allowed is removed.
- **Config-driven:** Schemes, attributes, relative-link flags and the max input length come from config; the built sanitizer is memoised and rebuilt when config changes.
- **Untrusted-source oriented:** Designed for HTML rendered from third-party GitHub READMEs and imported article bodies where raw HTML is enabled.

### Usage

@verbatim
<code-snippet name="clean-usage" lang="php">
use JeffersonGoncalves\HtmlSanitizer\HtmlSanitizer;

$clean = HtmlSanitizer::clean($untrustedHtml);
</code-snippet>
@endverbatim

### What is kept vs stripped

| Kept | Stripped |
|------|----------|
| Safe elements (headings, lists, tables, code, images, links) | `<script>`, `<style>` |
| `class` / `id` attributes (any element) | Event handlers (`onerror`, `onclick`, ...) |
| `width` / `height` on `<img>` | Alpine `x-*` attributes |
| Relative links/medias | Unsafe link/media schemes (`javascript:`, `data:`, `vbscript:`, ...) |
| `https`, `http`, `mailto` link schemes; `https`, `http` media schemes | everything else (note: `data` is excluded from media schemes — it can carry `svg+xml` script) |

### Conventions
- Keep the API static (`HtmlSanitizer::clean()`); do not introduce a facade unless the package conventions change.
- Sanitize untrusted HTML AFTER rendering Markdown (raw HTML enabled) and BEFORE display.
- Any post-processing that re-adds safe attributes (target/rel, lazy loading, table wrappers) should run AFTER `clean()`.
- The allowlist is intentionally narrow — widen it via `config/html-sanitizer.php` only with a clear, scoped reason. Never add `data` to `media_schemes` for untrusted input.
