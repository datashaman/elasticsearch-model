<?php

namespace Datashaman\Elasticsearch\Model;

use ArrayAccess;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as LengthAwarePaginatorContract;
use Illuminate\Contracts\Pagination\Presenter;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
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

            $results  = collect($response['hits']['hits'])->map(function ($hit) {
                return new Response\Result($hit);
            });

            /**
             * Must calculate current page manually here, can't use method because it uses the paginator for its result (infinite loop)
             */
            $from = $this->from();
            $perPage = $this->perPage();

            if (! is_null($from) && ! empty($perPage)) {
                $currentPage = $from / $perPage + 1;
            } else {
                $currentPage = null;
            }

            $this->attributes->put('results', new LengthAwarePaginator($results, $this->total(), $perPage, $currentPage));
        }

        return $this->attributes->get('results');
    }

    /**
     * Return a collection of the IDs of the hits.
     *
     * @return \Illuminate\Support\Collection
     */
    public function ids()
    {
        $response = $this->response();

        return collect($response['hits']['hits'])
            ->map(function ($hit) {
                return $hit['_id'];
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

    public function from()
    {
        return array_get($this->search->definition, 'from');
    }

    public function size()
    {
        return array_get($this->search->definition, 'size');
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

    public function total()
    {
        return array_get($this->response(), 'hits.total');
    }

    /**
     * Delegates
     */
    public function __call($name, $args)
    {
        return call_user_func_array([$this->results(), $name], $args);
    }

    /*
    public function toArray()
    {
        return $this->results()->toArray();
    }

    public function offsetExists($offset)
    {
        return $this->results()->offsetExists($offset);
    }

    public function offsetGet($offset)
    {
        return $this->results()->offsetGet($offset);
    }

    public function offsetUnset($offset)
    {
        return $this->results()->offsetUnset($offset);
    }

    public function offsetSet($offset, $value)
    {
        return $this->results()->offsetSet($offset, $value);
    }

    public function toJson($options=[])
    {
        return $this->results()->toJson($options);
    }

    public function count()
    {
        return $this->results()->count();
    }

    public function getIterator()
    {
        return $this->results()->getIterator();
    }

    public function lastPage()
    {
        return $this->results()->lastPage();
    }

    public function url($page)
    {
        return $this->results()->url($page);
    }

    public function appends($key, $value = null)
    {
        return $this->results()->appends($key, $value);
    }

    public function fragment($fragment = null)
    {
        return $this->results()->fragment($fragment);
    }

    public function nextPageUrl()
    {
        return $this->results()->nextPageUrl();
    }

    public function previousPageUrl()
    {
        return $this->results()->previousPageUrl();
    }

    public function items()
    {
        return $this->results()->items();
    }

    public function firstItem()
    {
        return $this->results()->firstItem();
    }

    public function lastItem()
    {
        return $this->results()->lastItem();
    }

    public function currentPage()
    {
        return $this->results()->currentPage();
    }

    public function hasPages()
    {
        return $this->results()->hasPages();
    }

    public function hasMorePages()
    {
        return $this->results()->hasMorePages();
    }

    public function isEmpty()
    {
        return $this->results()->isEmpty();
    }

    public function render(Presenter $presenter = null)
    {
        return $this->results()->render();
    }

    public function getCollection()
    {
        return $this->results()->getCollection();
    }
     */
}
