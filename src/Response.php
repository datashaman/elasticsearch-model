<?php

namespace Datashaman\Elasticsearch\Model;

use ArrayAccess;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use TypeError;

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
            $resultFactory = array_get($this->options, 'resultFactory', Response\Result::class);

            // If we are given a string, create a callable that
            // creates a new object from the class name
            if (is_string($resultFactory)) {
                $className = $resultFactory;
                $resultFactory = function ($hit) use ($className) {
                    return new $className($hit);
                };
            }

            $response = $this->response();

            $results = collect($response['hits']['hits'])
                ->map(
                    function ($hit) use ($resultFactory) {
                        $result = call_user_func($resultFactory, $hit);

                        if (!is_object($result)) {
                            throw new TypeError('Return value of resultFactory must be an instance of Datashaman\Elasticsearch\Model\Result');
                        }

                        if (!$result instanceof Response\Result) {
                            throw new TypeError('Return value of resultFactory must be an instance of Datashaman\Elasticsearch\Model\Result, instance of ' . get_class($result) . ' returned');
                        }
                        return $result;
                    }
                );

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
