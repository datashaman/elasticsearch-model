<?php

namespace Datashaman\Elasticsearch\Model\Driver;

class DefaultDriver extends Base
{
    public function records()
    {
        $class = $this->class;
        $records = $class::whereIn('id', $this->records->ids);

        return $records;
    }
}
