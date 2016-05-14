<?php

namespace Oneafricamedia\Horizon;

use Illuminate\Support\ServiceProvider;

class HorizonServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(dirname(__DIR__).'/views', 'horizon');

        $this->publishes([
            __DIR__.'/../views' => base_path('resources/views/vendor/horizon'),
            __DIR__.'/../schemas' => base_path('resources/schemas'),
            __DIR__.'/../config/horizon.php' => config_path('horizon.php'),
        ]);

        $this->app->singleton('Oneafricamedia\Horizon\ParserContract', function ($app) {
            return new Parser($app['config']['horizon']);
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/horizon.php', 'horizon'
        );
    }
}
