<?php namespace Datashaman\ElasticModel;

use Illuminate\Support\Manager;

class DriverManager extends Manager
{
    private $response;

    public function __construct($response)
    {
        parent::__construct(null);
        $this->response = $response;
    }

    public function getDefaultDriver()
    {
        return 'eloquent';
    }

    public function createEloquentDriver()
    {
        return new Driver\EloquentDriver($this->response);
    }
}
