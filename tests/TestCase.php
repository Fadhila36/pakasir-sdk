<?php

namespace Fadhila36\Pakasir\Tests;

use Fadhila36\Pakasir\PakasirServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            PakasirServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('pakasir.project', 'test-project');
        $app['config']->set('pakasir.api_key', 'test-api-key');
        $app['config']->set('pakasir.base_url', 'https://api.pakasir.com');
    }
}
