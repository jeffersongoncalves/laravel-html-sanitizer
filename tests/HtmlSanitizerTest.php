<?php

use JeffersonGoncalves\HtmlSanitizer\HtmlSanitizer;

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
