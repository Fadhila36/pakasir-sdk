<?php

declare(strict_types=1);

namespace Fadhila36\Pakasir;

use Fadhila36\Pakasir\Contracts\PakasirInterface;
use Illuminate\Support\ServiceProvider;

class PakasirServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/pakasir.php' => config_path('pakasir.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/pakasir.php', 'pakasir');

        $this->app->singleton(PakasirInterface::class, function ($app) {
            $config = $app['config']['pakasir'] ?? [];

            return new Pakasir(
                project: (string) ($config['project'] ?? ''),
                apiKey: (string) ($config['api_key'] ?? ''),
                baseUrl: (string) ($config['base_url'] ?? 'https://app.pakasir.com/api'),
                timeout: (int) ($config['timeout'] ?? 30),
                retryAttempts: (int) ($config['retry_attempts'] ?? 3),
                retryDelay: (int) ($config['retry_delay'] ?? 100),
                loggingEnabled: (bool) ($config['logging_enabled'] ?? false)
            );
        });

        $this->app->alias(PakasirInterface::class, 'pakasir');
    }
}
