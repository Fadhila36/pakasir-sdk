<?php

namespace Fadhila36\Pakasir;

use Illuminate\Support\ServiceProvider;

class PakasirServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/pakasir.php' => config_path('pakasir.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/pakasir.php', 'pakasir');

        $this->app->singleton('pakasir', function ($app) {
            $config = $app['config']['pakasir'];
            
            return new Pakasir(
                $config['project'],
                $config['api_key'],
                $config['base_url']
            );
        });
    }
}
