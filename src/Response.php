<?php

namespace Datashaman\Elasticsearch\Model;

use ArrayAccess;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class Response implements ArrayAccess
{
    use ArrayDelegateMethod;
    protected static $arrayDelegate = 'results';

    use Response\Pagination;
    use Response\Delegates;

    protected $search;
    protected $options;
    protected $attributes;

    /**
     * Create a response instance.
     *
     * @param  SearchRequest $search
     * @param  array         $options
     */
    public function __construct(SearchRequest $search, array $options = [])
    {
        $this->search = $search;
        $this->options = $options;
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
            $resultFactory = array_get($this->options, 'resultFactory')
                ?: config('elasticsearch.resultFactory');

            if (empty($resultFactory)) {
                $resultFactory = function ($hit) {
                    $resultClass = array_get(
                        $this->options,
                        'resultClass',
                        config(
                            'elasticsearch.resultClass',
                            Response\Result::class
                        )
                    );
                    return new $resultClass($hit);
                };
            } else {
                if (!is_callable($resultFactory)) {
                    throw new Exception('Result factory must be callable');
                }
            }

            $response = $this->response();

            $results = collect($response['hits']['hits'])
                ->map($resultFactory);

            /*
             * Must calculate current page manually here, can't use method because it uses the paginator for its result (infinite loop)
             */
            $from = $this->from();
            $perPage = $this->perPage();

            $currentPage = (! is_null($from) && ! empty($perPage)) ? $from / $perPage + 1 : null;

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

    /**
     * @return Collection
     */
    public function getAttributes()
    {
        return $this->attributes;
    }
}
