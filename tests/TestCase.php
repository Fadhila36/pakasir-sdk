<?php

declare(strict_types=1);

namespace Fadhila36\Pakasir\Tests;

use Fadhila36\Pakasir\PakasirServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    /**
     * Get package providers.
     */
    protected function getPackageProviders($app): array
    {
        return [
            PakasirServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('pakasir.project', 'test-project');
        $app['config']->set('pakasir.api_key', 'test-api-key');
        $app['config']->set('pakasir.base_url', 'https://app.pakasir.com/api');
        $app['config']->set('pakasir.timeout', 5);
        $app['config']->set('pakasir.retry_attempts', 1);
        $app['config']->set('pakasir.retry_delay', 0);
        $app['config']->set('pakasir.logging_enabled', false);
    }
}
