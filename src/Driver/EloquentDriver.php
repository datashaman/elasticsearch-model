<?php namespace Datashaman\ElasticModel\Driver;

class EloquentDriver extends Base
{
    public function records()
    {
        $ids = $this->response->ids();

        $class = $this->response->search->class;
        $records = $class::whereIn('id', $ids);

        if (array_has($this->options, 'with')) {
            call_user_func_array([ $records, 'with' ], $this->options['with']);
        }

        $records = $records->get();

        $sorted = collect();

        foreach($ids as $id) {
            $record = $records->first(function ($index, $record) use ($id) {
                return $record->id == $id;
            });

            if (!empty($record)) {
                $sorted->push($record);
            }
        }

        return $sorted;
    }
}
