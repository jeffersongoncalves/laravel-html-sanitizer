<?php

namespace JeffersonGoncalves\HtmlSanitizer\Tests;

use JeffersonGoncalves\HtmlSanitizer\HtmlSanitizerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            HtmlSanitizerServiceProvider::class,
        ];
    }
}
