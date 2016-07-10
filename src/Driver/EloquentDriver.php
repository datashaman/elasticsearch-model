<?php namespace Datashaman\Elasticsearch\Model\Driver;

class EloquentDriver extends Base
{
    public function records()
    {
        $ids = $this->response->ids();

        $class = $this->response->search->class;
        $builder = $class::whereIn('id', $ids);

        if (array_has($this->options, 'with')) {
            call_user_func_array([ $builder, 'with' ], $this->options['with']);
        }

        if (is_callable($this->callable)) {
            call_user_func($this->callable, $builder);
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
}
