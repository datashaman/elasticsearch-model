<?php namespace Datashaman\ElasticModel;

use Datashaman\ElasticModel\Response;


class SearchRequest
{
    public $class;
    public $definition;
    public $options;

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
