<?php namespace Datashaman\ElasticModel;

use ArrayAccess;
use Datashaman\ElasticModel\ArrayDelegate;

class Response implements ArrayAccess
{
    use Response\Pagination;

    use ArrayDelegate;
    protected static $arrayDelegate = 'results';

    public $search;
    public $response;

    protected $results;

    public function __construct($search, $response=null)
    {
        $this->search = $search;
        $this->response = is_null($response) ? $search->execute() : $response;

        $this->results = collect($this->response['hits']['hits'])->map(function ($hit) {
            $result = new Response\Result($hit);
            return $result;
        });

        /*
        foreach ($this->response['hits']['hits'] as $hit) {
            $result = new Response\Result($hit);
            $this->results[] = $result;
        }
         */
    }

    public function __call($name, $args)
    {
        return call_user_func_array([ $this->results, $name ], $args);
    }

    public function ids()
    {
        return $this->results->map(function ($result) { return $result->id; });
    }

    public function records($options=[], callable $callable=null)
    {
        return new Response\Records($this, $options, $callable);
    }

    public function took()
    {
        return $this->response['took'];
    }

    public function timedOut()
    {
        return $this->response['timed_out'];
    }

    public function shards()
    {
        return $this->response['_shards'];
    }

    public function aggregations()
    {
        return array_get($this->response, 'aggregations');
    }

    public function suggestions()
    {
        return array_has($this->response, 'suggest') ? new Response\Suggestions($this->response['suggest']) : null;
    }

    public function from()
    {
        return $this->search->definition['from'];
    }

    public function size()
    {
        return $this->search->definition['size'];
    }

    public function total()
    {
        return $this->response['hits']['total'];
    }
}
