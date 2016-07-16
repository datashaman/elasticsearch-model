<?php

namespace Datashaman\Elasticsearch\Model;

use ArrayAccess;
use Illuminate\Pagination\LengthAwarePaginator;

class Response implements ArrayAccess
{
    use ArrayDelegateMethod;
    protected static $arrayDelegate = 'results';

    use Response\Pagination;
    use Response\Delegates;

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

            $results = collect($response['hits']['hits'])->map(function ($hit) {
                return new Response\Result($hit);
            });

            /*
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

    public function suggestions()
    {
        return array_has($this->response(), 'suggest') ? new Response\Suggestions(array_get($this->response(), 'suggest')) : null;
    }
}
