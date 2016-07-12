<?php

namespace Datashaman\Elasticsearch\Model;

use ArrayAccess;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;

class Response implements ArrayAccess
{
    use ArrayDelegate;
    protected static $arrayDelegate = 'results';

    use Response\Pagination;

    public $search;
    protected $attributes;

    /**
     * Create a response instance.
     *
     * @param  SearchRequest $search
     * @param  array         $response Dummy response (used by testing)
     */
    public function __construct(SearchRequest $search, $response = null)
    {
        $this->search = $search;
        $this->attributes = collect();

        if (! is_null($response)) {
            $this->attributes->put('response', $response);
        }
    }

    /**
     * Attribute getters (for lazy loading).
     *
     * @param  string $name
     * @return mixed
     */
    public function __get($name)
    {
        if ($name == 'response') {
            if (! $this->attributes->has('response')) {
                $this->attributes->put('response', $this->search->execute());
            }

            return $this->attributes->get('response');
        }

        if ($name == 'results') {
            if (! $this->attributes->has('results')) {
                $this->attributes->put('results', collect($this->response['hits']['hits'])->map(function ($hit) {
                    $result = new Response\Result($hit);

                    return $result;
                }));
            }

            return $this->attributes->get('results');
        }

        if ($name == 'paginator') {
            if (! $this->attributes->has('paginator')) {
                $this->attributes->put('paginator', new LengthAwarePaginator($this->results, $this->total(), $this->perPage(), $this->currentPage()));
            }

            return $this->attributes->get('paginator');
        }
    }

    /**
     * Delegate all unknown calls to the results collection.
     *
     * @param string $name
     * @param array  $args
     * @return mixed
     */
    public function __call($name, $args)
    {
        return call_user_func_array([$this->results, $name], $args);
    }

    public function ids()
    {
        return $this->results->map(function ($result) {
            return $result->id;
        });
    }

    public function records($options = [], callable $callable = null)
    {
        return new Response\Records($this, $options, $callable);
    }

    public function took()
    {
        return array_get($this->response, 'took');
    }

    public function timedOut()
    {
        return array_get($this->response, 'timed_out');
    }

    public function shards()
    {
        return array_get($this->response, '_shards');
    }

    public function aggregations()
    {
        return array_get($this->response, 'aggregations');
    }

    public function suggestions()
    {
        return array_has($this->response, 'suggest') ? new Response\Suggestions(array_get($this->response, 'suggest')) : null;
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
        return array_get($this->response, 'hits.total');
    }

    public function setPath($path)
    {
        $this->paginator->setPath($path);

        return $this;
    }
}
