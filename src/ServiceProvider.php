<?php

namespace Datashaman\Elasticsearch\Model;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $configPath = __DIR__.'/../config/elasticsearch.php';
        $this->mergeConfigFrom($configPath, 'elasticsearch');

        $this->app->singleton('elasticsearch', function ($app) {
            $clientFactory = array_get(
                $app['config'],
                'elasticsearch.clientFactory',
                [
                    ClientFactory::class,
                    'make'
                ]
            );

            return call_user_func($clientFactory, $app);
        });
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $configPath = __DIR__.'/../config/elasticsearch.php';
        $this->publishes([$configPath => config_path('elasticsearch.php')], 'config');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['elasticsearch'];
    }
}
