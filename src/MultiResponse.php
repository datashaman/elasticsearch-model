<?php

namespace Datashaman\Elasticsearch\Model;

use Exception;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class MultiResponse extends Response
{
    public function response()
    {
        return array_first($this->responses());
    }

    public function responses()
    {
        if (! $this->attributes->has('responses')) {
            $response = $this->search->execute();
            $this->attributes->put('responses', $response['responses']);
        }

        return $this->attributes->get('responses');
    }

    public function results()
    {
        if (! $this->attributes->has('results')) {
            $resultFactory = $this->resultFactory();

            $responses = $this->responses();

            $combinedResults = [];

            foreach ($responses as $index => $response) {
                $hits = $response['hits']['hits'];
                $query = $this->search->definition['body'][$index * 2 + 1];

                // If perPage is 0, then this is a count search type.
                // No results will be returned (and perPage of 0 triggers
                // a Division by Zero error), so return a dummy paginator
                // with no items and a default perPage size.
                $perPage = array_get($query, 'size', 10);

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
                $from = array_get($query, 'from', 0);
                $currentPage = (! is_null($from) && ! empty($perPage)) ? $from / $perPage + 1 : null;
                $combinedResults[] = new LengthAwarePaginator($results, array_get($response, 'hits.total'), $perPage, $currentPage);
            }

            $this->attributes->put('results', $combinedResults);
        }

        return $this->attributes->get('results');
    }
}
