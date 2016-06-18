<?php namespace Datashaman\ElasticModel\Traits;

use Datashaman\ElasticModel\Response;


class SearchRequest
{
    protected $klass;
    protected $options;
    protected $definition;

    public function __construct($klass, $query, $options=[])
    {
        $this->klass = $klass;
        $this->options = $options;

        $index = array_get($options, 'index', $klass::indexName());
        $type = array_get($options, 'type', $klass::documentType());

        if (method_exists($query, 'toArray')) {
            $body = $query->toArray();
        } elseif (is_string($query) && preg_match('/^\s*{/', $query)) {
            $body = $query;
        } else {
            $q = $query;
        }

        $this->definition = array_merge(compact('index', 'type', 'body', 'q'), $options);
    }

    public function execute()
    {
        $klass = $this->klass;
        return $klass->client()->search($this->definition);
    }
}

trait Searching
{
    public static function search($query, $options=[])
    {
        $search = new SearchRequest(static::class, $query, $options);
        return new Response(static::class, $search);
    }
}
