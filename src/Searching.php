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

        $index = array_get($options, 'index', $class::elastic()->indexName());
        $type = array_get($options, 'type', $class::elastic()->documentType());

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
        $class = $this->class;

        if (!empty($this->definition['q'])) {
            $query = array_pull($this->definition, 'q');
            $this->definition['body'] = [
                'query' => [
                    'query_string' => compact('query'),
                ],
            ];
        }

        $result = $class::elastic()->client()->search($this->definition);
        return $result;
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
