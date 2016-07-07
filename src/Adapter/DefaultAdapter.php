<?php namespace Datashaman\ElasticModel\Adapter;

use Datashaman\ElasticModel\Adapter;

class DefaultAdapter extends Adapter
{
    public function records()
    {
        $class = $this->class;
        $records = $class::whereIn($this->records->ids);
        return $records;
    }
}
