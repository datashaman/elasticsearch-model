<?php

namespace Datashaman\Elasticsearch\Model\Driver;

class Base
{
    public $driverManager;

    public function __construct($driverManager)
    {
        $this->driverManager = $driverManager;
    }
}
