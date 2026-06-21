<?php

namespace JeffersonGoncalves\HtmlSanitizer;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class HtmlSanitizerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-html-sanitizer')
            ->hasConfigFile();
    }
}
