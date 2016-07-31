<?php

namespace Datashaman\Elasticsearch\Model;

use Illuminate\Support\Manager;

class DriverManager extends Manager
{
    public $class;

    public function __construct($class)
    {
        parent::__construct(null);
        $this->class = $class;
    }

    public function getDefaultDriver()
    {
        return 'eloquent';
    }

    public function createEloquentDriver()
    {
        return new Driver\EloquentDriver($this);
    }
}
