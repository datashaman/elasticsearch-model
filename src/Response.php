<?php

namespace Datashaman\Elasticsearch\Model;

use ArrayAccess;
use Countable;
use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class Response implements ArrayAccess, Countable
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
            $hits = $response['hits']['hits'];

            // If perPage is 0, then this is a count search type.
            // No results will be returned (and perPage of 0 triggers
            // a Division by Zero error), so return a dummy paginator
            // with no items and a default perPage size.
            $perPage = $this->perPage();

            if ($perPage === 0 && count($hits) === 0) {
                return new LengthAwarePaginator([], 0, 10);
            }

            $results = collect($hits)->map($resultFactory);

            // We can't let perPage of 0 through, it causes division by zero error in paginator.
            if ($perPage === 0) {
                $perPage = $results->count();
            }

            // Must calculate current page manually here, can't use method
            // because it uses the paginator for its result (infinite loop)
            $from = $this->from();
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

    public function withResults(LengthAwarePaginator $results): self
    {
        $this->attributes->put('results', $results);

        return $this;
    }
}
