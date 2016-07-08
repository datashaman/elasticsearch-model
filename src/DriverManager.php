<?php namespace Datashaman\ElasticModel;

use Illuminate\Support\Manager;

class DriverManager extends Manager
{
    private $response;
    private $options;

    public function __construct($response, $options=[])
    {
        parent::__construct(null);
        $this->response = $response;
        $this->options = $options;
    }

    public function getDefaultDriver()
    {
        return 'eloquent';
    }

    public function createEloquentDriver()
    {
        return new Driver\EloquentDriver($this->response, $this->options);
    }
}
