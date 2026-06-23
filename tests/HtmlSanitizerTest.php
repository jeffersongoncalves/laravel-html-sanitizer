<?php

use JeffersonGoncalves\HtmlSanitizer\HtmlSanitizer;

// The sanitizer is memoised per config signature; flush so each test that
// overrides config starts from a clean slate regardless of order.
beforeEach(fn () => HtmlSanitizer::flush());

it('strips script tags', function () {
    $clean = HtmlSanitizer::clean('<p>Hello</p><script>alert("xss")</script>');

    expect($clean)
        ->not->toContain('<script')
        ->not->toContain('alert(')
        ->toContain('<p>Hello</p>');
});

it('strips onerror event handlers', function () {
    $clean = HtmlSanitizer::clean('<img src="https://example.com/a.png" onerror="alert(1)">');

    expect($clean)
        ->not->toContain('onerror')
        ->not->toContain('alert(1)');
});

it('strips onclick event handlers', function () {
    $clean = HtmlSanitizer::clean('<button onclick="steal()">Click</button>');

    expect($clean)->not->toContain('onclick');
});

it('strips alpine x- attributes', function () {
    $clean = HtmlSanitizer::clean('<div x-data="{ open: false }" x-on:click="open = true">Menu</div>');

    expect($clean)
        ->not->toContain('x-data')
        ->not->toContain('x-on:click');
});

it('keeps heading tags', function () {
    $clean = HtmlSanitizer::clean('<h1>Title</h1>');

    expect($clean)->toContain('<h1>Title</h1>');
});

it('keeps table tags', function () {
    $html = '<table><thead><tr><th>A</th></tr></thead><tbody><tr><td>1</td></tr></tbody></table>';

    $clean = HtmlSanitizer::clean($html);

    expect($clean)
        ->toContain('<table>')
        ->toContain('<th>A</th>')
        ->toContain('<td>1</td>');
});

it('keeps links with safe schemes', function () {
    $clean = HtmlSanitizer::clean('<a href="https://example.com">Link</a>');

    expect($clean)
        ->toContain('href="https://example.com"')
        ->toContain('Link');
});

it('keeps width attribute on images', function () {
    $clean = HtmlSanitizer::clean('<img src="https://example.com/a.png" width="20%">');

    expect($clean)->toContain('width="20%"');
});

it('keeps class and id attributes', function () {
    $clean = HtmlSanitizer::clean('<p class="lead" id="intro">Hello</p>');

    expect($clean)
        ->toContain('class="lead"')
        ->toContain('id="intro"');
});

it('does not truncate input larger than 20KB', function () {
    // Symfony's HtmlSanitizer truncates at 20000 bytes by default; the marker
    // sits well past that boundary, so it would disappear if the cap applied.
    $filler = str_repeat('<p>lorem ipsum dolor sit amet</p>', 1000);
    $marker = '<p id="end-marker">tail content</p>';
    $html = $filler.$marker;

    expect(strlen($html))->toBeGreaterThan(20000);

    $clean = HtmlSanitizer::clean($html);

    expect($clean)
        ->toContain('id="end-marker"')
        ->toContain('tail content');
});

it('strips javascript: hrefs', function () {
    $clean = HtmlSanitizer::clean('<a href="javascript:alert(1)">x</a>');

    expect($clean)
        ->not->toContain('javascript:')
        ->not->toContain('alert(1)');
});

it('strips data: hrefs', function () {
    $clean = HtmlSanitizer::clean('<a href="data:text/html;base64,PHNjcmlwdD4=">x</a>');

    expect($clean)->not->toContain('data:text/html');
});

it('strips vbscript: hrefs', function () {
    $clean = HtmlSanitizer::clean('<a href="vbscript:msgbox(1)">x</a>');

    expect($clean)
        ->not->toContain('vbscript:')
        ->not->toContain('msgbox');
});

it('removes iframe elements', function () {
    $clean = HtmlSanitizer::clean('<iframe src="https://evil.test"></iframe><p>ok</p>');

    expect($clean)
        ->not->toContain('<iframe')
        ->toContain('<p>ok</p>');
});

it('removes object elements', function () {
    $clean = HtmlSanitizer::clean('<object data="evil.swf"></object><p>ok</p>');

    expect($clean)
        ->not->toContain('<object')
        ->toContain('<p>ok</p>');
});

it('removes embed elements', function () {
    $clean = HtmlSanitizer::clean('<embed src="evil.swf"><p>ok</p>');

    expect($clean)
        ->not->toContain('<embed')
        ->toContain('<p>ok</p>');
});

it('removes form elements', function () {
    $clean = HtmlSanitizer::clean('<form action="https://evil.test"><input name="x"></form><p>ok</p>');

    expect($clean)
        ->not->toContain('<form')
        ->not->toContain('<input')
        ->toContain('<p>ok</p>');
});

it('removes style elements', function () {
    $clean = HtmlSanitizer::clean('<style>body{display:none}</style><p>ok</p>');

    expect($clean)
        ->not->toContain('<style')
        ->not->toContain('display:none')
        ->toContain('<p>ok</p>');
});

it('strips various on* event handler attributes', function () {
    $clean = HtmlSanitizer::clean('<div onmouseover="a()" onload="b()" onfocus="c()">hover</div>');

    expect($clean)
        ->not->toContain('onmouseover')
        ->not->toContain('onload')
        ->not->toContain('onfocus')
        ->toContain('hover');
});

it('neutralises a nested obfuscated payload', function () {
    // A script smuggled inside otherwise-allowed markup must still be removed,
    // and the surviving anchor must not carry the javascript: scheme.
    $html = '<div><p>intro</p><script>document.cookie</script>'
        .'<a href="JaVaScRiPt:alert(document.domain)">click</a></div>';

    $clean = HtmlSanitizer::clean($html);

    expect($clean)
        ->not->toContain('<script')
        ->not->toContain('document.cookie')
        ->not->toContain('alert(')
        ->toContain('intro')
        ->toContain('click');

    expect(strtolower($clean))->not->toContain('javascript:');
});

it('drops data: image sources because media schemes exclude data', function () {
    // 'data' is intentionally absent from the default media_schemes config:
    // data:image/svg+xml can carry executable script. Documenting that here.
    $clean = HtmlSanitizer::clean('<img src="data:image/svg+xml,<svg onload=alert(1)>">');

    expect($clean)
        ->not->toContain('data:image/svg+xml')
        ->not->toContain('onload')
        ->not->toContain('alert(1)');
});

// --- Config overrides -------------------------------------------------------

it('strips mailto links when removed from the configured link schemes', function () {
    config()->set('html-sanitizer.link_schemes', ['https', 'http']);

    expect(HtmlSanitizer::clean('<a href="mailto:a@b.com">mail</a>'))
        ->not->toContain('mailto:');
});

it('keeps data: media sources when data is added to the configured media schemes', function () {
    config()->set('html-sanitizer.media_schemes', ['https', 'http', 'data']);

    expect(HtmlSanitizer::clean('<img src="data:image/png;base64,iVBORw0KGgo=">'))
        ->toContain('data:image/png');
});

it('strips relative links when allow_relative_links is disabled', function () {
    config()->set('html-sanitizer.allow_relative_links', false);

    expect(HtmlSanitizer::clean('<a href="/docs">L</a>'))
        ->not->toContain('href="/docs"');
});

// --- Config type-guards -----------------------------------------------------

it('falls back to default link schemes when the config value is not an array', function () {
    config()->set('html-sanitizer.link_schemes', 'https'); // invalid: not an array

    expect(HtmlSanitizer::clean('<a href="https://example.com">L</a>'))
        ->toContain('href="https://example.com"');
});

it('falls back to the default relative-link flag when the config value is not boolean', function () {
    config()->set('html-sanitizer.allow_relative_links', 'yes'); // invalid: not a bool

    expect(HtmlSanitizer::clean('<a href="/docs">L</a>'))
        ->toContain('href="/docs"');
});

it('allows no custom attributes when the attributes config is not an array', function () {
    config()->set('html-sanitizer.attributes', 'nope'); // invalid: not an array

    expect(HtmlSanitizer::clean('<p class="lead">Hi</p>'))
        ->not->toContain('class="lead"')
        ->toContain('Hi');
});

it('skips non-string attribute names and honours string element specs', function () {
    config()->set('html-sanitizer.attributes', [
        0 => '*',           // non-string name → skipped, no error
        'data-test' => '*', // string spec → allowed on every element
    ]);

    expect(HtmlSanitizer::clean('<p data-test="1">Hi</p>'))
        ->toContain('data-test="1"');
});

// --- Additional XSS vectors -------------------------------------------------

it('strips inline style attributes', function () {
    expect(HtmlSanitizer::clean('<p style="position:fixed;top:0">x</p>'))
        ->not->toContain('style=')
        ->toContain('x');
});

it('removes svg and math elements', function () {
    $clean = HtmlSanitizer::clean('<svg><circle r="1"/></svg><math><mi>x</mi></math><p>ok</p>');

    expect($clean)
        ->not->toContain('<svg')
        ->not->toContain('<math')
        ->toContain('<p>ok</p>');
});

it('strips an entity-encoded javascript scheme', function () {
    $clean = HtmlSanitizer::clean('<a href="java&#115;cript:alert(1)">x</a>');

    expect(strtolower($clean))->not->toContain('javascript:');
    expect($clean)->not->toContain('alert(1)');
});

it('neutralises a script tag that is never closed', function () {
    $clean = HtmlSanitizer::clean('<p>intro</p><script>alert(1)');

    expect($clean)
        ->not->toContain('<script')
        ->not->toContain('alert(1)')
        ->toContain('intro');
});
