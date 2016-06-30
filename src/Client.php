<?php namespace Datashaman\ElasticModel;

use Elasticsearch\ClientBuilder;

trait Client
{
    /**
     * Get/set the client for a specific model class
     */
    public static function client()
    {
        return static::getOrSetStatic(func_get_args(), 'client', ClientBuilder::create()->build());
    }
}
