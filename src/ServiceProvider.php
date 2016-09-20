<?php

namespace Datashaman\Elasticsearch\Model;

use Elasticsearch\ClientBuilder;

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
            $config = array_get(
                $app['config'],
                'elasticsearch',
                [
                    'hosts' => '127.0.0.1:9200',
                ]
            );

            $client = ClientBuilder::fromConfig($config, true);

            return $client;
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
