<?php namespace Datashaman\ElasticModel;

use Elasticsearch\ClientBuilder;
use Illuminate\Support\Fluent;

class Elasticsearch extends Fluent
{
    public function __construct($class)
    {
        parent::__construct();

        $this->client = ClientBuilder::create()->build();

        $documentType = str_slug(last(explode('\\', $class)));

        $this->documentType = $documentType;
        $this->indexName = str_plural($documentType);
    }
}

trait Proxy
{
    protected static $elasticsearch;

    protected static function elastic()
    {
        if (!isset(static::$elasticsearch)) {
            static::$elasticsearch = new Elasticsearch(static::class);
        }

        return static::$elasticsearch;
    }
}
