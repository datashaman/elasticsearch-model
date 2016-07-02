<?php namespace Datashaman\ElasticModel;

use Datashaman\ElasticModel\Response;
use Illuminate\Support\Fluent;

class SearchRequest
{
    public $class;
    public $options;
    public $definition;

    public function __construct($class, $query, $options=[])
    {
        $this->class = $class;
        $this->options = $options;

        $index = array_get($options, 'index', $class::elastic()->indexName);
        $type = array_get($options, 'type', $class::elastic()->documentType);

        if (method_exists($query, 'toArray')) {
            $body = $query->toArray();
        } elseif (is_string($query) && preg_match('/^\s*{/', $query)) {
            $body = $query;
        } else {
            $body = [
                'query' => [
                    'query_string' => compact('query'),
                ],
            ];
        }

        $this->definition = array_merge(compact('index', 'type', 'body'), $options);
    }

    public function execute()
    {
        $class = $this->class;
        $result = $class::elastic()->client->search($this->definition);
        return $result;
    }

    public function update($attributes)
    {
        foreach ($attributes as $key => $value) {
            $this->definition[$key] = $value;
        }
    }
}

trait Searching
{
    public static function search($query, $options=[])
    {
        $search = new SearchRequest(static::class, $query, $options);
        $response = new Response(static::class, $search);
        return $response;
    }
}
