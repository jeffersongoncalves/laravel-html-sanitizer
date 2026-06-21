<?php

// config for JeffersonGoncalves/HtmlSanitizer
return [
    /*
    |--------------------------------------------------------------------------
    | Maximum input length
    |--------------------------------------------------------------------------
    |
    | Symfony's HtmlSanitizer truncates input to 20000 bytes by default, which
    | silently cuts long READMEs/articles mid-content. Use -1 for unlimited
    | (recommended for trusted-length but untrusted-content sources), or set a
    | positive byte count to cap input length.
    |
    */
    'max_input_length' => -1,

    /*
    |--------------------------------------------------------------------------
    | Relative links / medias
    |--------------------------------------------------------------------------
    |
    | Allow relative href/src values (e.g. "/docs", "./image.png").
    |
    */
    'allow_relative_links' => true,
    'allow_relative_medias' => true,

    /*
    |--------------------------------------------------------------------------
    | Allowed link schemes
    |--------------------------------------------------------------------------
    |
    | Schemes permitted on <a href>. Anything else (javascript:, data:,
    | vbscript:, ...) is stripped.
    |
    */
    'link_schemes' => ['https', 'http', 'mailto'],

    /*
    |--------------------------------------------------------------------------
    | Allowed media schemes
    |--------------------------------------------------------------------------
    |
    | Schemes permitted on media src (<img>, ...). "data" is intentionally
    | excluded: data: URIs can carry image/svg+xml payloads that execute
    | script in some contexts. Add 'data' here only if you must support inline
    | base64 images and trust the source.
    |
    */
    'media_schemes' => ['https', 'http'],

    /*
    |--------------------------------------------------------------------------
    | Allowed attributes
    |--------------------------------------------------------------------------
    |
    | Map of attribute name => allowed elements. Use '*' to allow on every
    | element, or an array of tag names to scope it. class/id are kept for
    | heading permalinks, code-language hints and table wrappers; width/height
    | are scoped to <img> for README image galleries.
    |
    */
    'attributes' => [
        'class' => '*',
        'id' => '*',
        'width' => ['img'],
        'height' => ['img'],
    ],
];
