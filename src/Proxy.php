<?php namespace Datashaman\ElasticModel;

use Elasticsearch\ClientBuilder;
use Illuminate\Support\Collection;

class Elasticsearch
{
    protected $class;
    protected $collection;

    public function __construct($class)
    {
        $this->class = $class;
        $this->collection = new Collection;
    }

    protected function getOrSet($args, $key, $default)
    {
        if (count($args) == 0) {
            if (!$this->collection->has($key)) {
                $this->collection->put($key, $default);
            }

            return $this->collection->get($key);
        }

        $this->collection->put($key, head($args));

        return $this->collection->get($key);
    }

    /**
     * Get/set the client
     */
    public function client()
    {
        return $this->getOrSet(func_get_args(), 'client', ClientBuilder::create()->build());
    }

    /**
     * Get/set the document type
     */
    public function documentType()
    {
        return $this->getOrSet(func_get_args(), 'documentType', str_slug(last(explode('\\', $this->class))));
    }

    /**
     * Get/set the index name
     */
    public function indexName()
    {
        return $this->getOrSet(func_get_args(), 'indexName', str_plural($this->documentType()));
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
