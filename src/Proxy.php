<?php namespace Datashaman\ElasticModel;

use Elasticsearch\ClientBuilder;
use Illuminate\Support\Fluent;

class Elasticsearch extends Fluent
{
    public function __construct($class)
    {
        parent::__construct();

        $this->client = ClientBuilder::create()->build();

        $name = preg_replace('/([A-Z])/', ' \1', class_basename($class));

        $this->documentType = isset($class::$documentType) ? $class::$documentType : str_slug($name);
        $this->indexName = isset($class::$indexName) ? $class::$indexName : str_slug(str_plural($name));
    }
}

trait Proxy
{
    protected static $elasticsearch;

    public static function elastic()
    {
        if (!isset(static::$elasticsearch)) {
            static::$elasticsearch = new Elasticsearch(static::class);
        }

        return static::$elasticsearch;
    }
}
