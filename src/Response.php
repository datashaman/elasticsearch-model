<?php

namespace Datashaman\Elasticsearch\Model;

use ArrayAccess;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;

class Response implements ArrayAccess
{
    use ArrayDelegateMethod;
    protected static $arrayDelegate = 'results';

    use Response\Pagination;

    protected $search;
    protected $attributes;

    /**
     * Create a response instance.
     *
     * @param  SearchRequest $search
     * @param  array         $response Dummy response (used by testing)
     */
    public function __construct(SearchRequest $search)
    {
        $this->search = $search;
        $this->attributes = collect();
    }

    public function search()
    {
        return $this->search;
    }

    public function response()
    {
        if (! $this->attributes->has('response')) {
            $this->attributes->put('response', $this->search->execute());
        }

        return $this->attributes->get('response');
    }

    public function results()
    {
        if (! $this->attributes->has('results')) {
            $response = $this->response();

            $this->attributes->put('results', collect($response['hits']['hits'])->map(function ($hit) {
                return new Response\Result($hit);
            }));
        }

        return $this->attributes->get('results');
    }

    public function paginator()
    {
        if (! $this->attributes->has('paginator')) {
            $this->attributes->put('paginator', new LengthAwarePaginator($this->results(), $this->total(), $this->perPage(), $this->currentPage()));
        }

        return $this->attributes->get('paginator');
    }

    /**
     * Delegate all unknown calls to the results collection.
     *
     * @param  string $name
     * @param  array  $args
     * @return mixed
     */
    public function __call($name, $args)
    {
        return call_user_func_array([$this->results(), $name], $args);
    }

    /**
     * Return a collection of the IDs of the results.
     *
     * @return \Illuminate\Support\Collection
     */
    public function ids()
    {
        return $this->results()->map(function ($result) {
            return $result->id;
        });
    }

    /**
     * Return a records object for this response.
     *
     * @param  array    $options
     * @param  callable $callable Called with the query to modify it on-the-fly.
     * @return Response\Records
     */
    public function records($options = [], callable $callable = null)
    {
        return new Response\Records($this, $options, $callable);
    }

    public function took()
    {
        return array_get($this->response(), 'took');
    }

    public function timedOut()
    {
        return array_get($this->response(), 'timed_out');
    }

    public function shards()
    {
        return array_get($this->response(), '_shards');
    }

    public function aggregations()
    {
        return array_get($this->response(), 'aggregations');
    }

    public function suggestions()
    {
        return array_has($this->response(), 'suggest') ? new Response\Suggestions(array_get($this->response(), 'suggest')) : null;
    }

    public function from()
    {
        return array_get($this->search->definition, 'from');
    }

    public function size()
    {
        return array_get($this->search->definition, 'size');
    }

    public function total()
    {
        return array_get($this->response(), 'hits.total');
    }
}
