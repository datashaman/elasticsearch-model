<?php

namespace Datashaman\Elasticsearch\Model\Driver;

class EloquentDriver extends Base
{
    public function records($response, $options = [], callable $callable = null)
    {
        $ids = $response->ids();

        $class = $this->driverManager->class;
        $builder = $class::whereIn('id', $ids);

        if (array_has($options, 'with')) {
            call_user_func_array([$builder, 'with'], $options['with']);
        }

        if (is_callable($callable)) {
            call_user_func($callable, $builder);
        }

        if (empty($builder->getQuery()->orders)) {
            /*
            # Only MySQL can use this, unfortunately.
            $idStrings = $ids->map(function ($id) { return "'$id'"; })->implode(', ');
            $records = $records->orderByRaw("find_in_set(id, $idStrings)")->get();
            */

            return $builder->get()->sortBy(function ($record) use ($ids) {
                return $ids->search($record->id);
            })->values();
        }

        return $builder->get();
    }

    public function findInChunks($options = [], callable $callable = null)
    {
        $query = array_pull($options, 'query');
        $scope = array_pull($options, 'scope');
        $preprocess = array_pull($options, 'preprocess');
        $chunkSize = array_pull($options, 'chunkSize', 1000);

        $class = $this->driverManager->class;

        $builder = empty($scope) ? (new $class)->newQuery() : $class::$scope();

        if (! empty($query)) {
            call_user_func($query, $builder);
        }

        $builder->chunk($chunkSize, function ($chunk) use ($preprocess, $callable, $class) {
            if (! empty($preprocess)) {
                $chunk = call_user_func([$class, $preprocess], $chunk);
            }

            call_user_func($callable, $chunk);
        });
    }
}
