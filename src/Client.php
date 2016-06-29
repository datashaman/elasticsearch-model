<?php namespace Datashaman\ElasticModel;

use Elasticsearch\ClientBuilder;

trait Client
{
    protected static $_client;

    /**
     * Get/set the client for a specific model class
     */
    public static function client()
    {
        if (func_num_args() == 0) {
            if (empty(static::$_client)) {
                static::$_client = ClientBuilder::create()->build();
            }

            return static::$_client;
        }

        $client = func_get_arg(0);
        static::$_client = $client;
    }
}
