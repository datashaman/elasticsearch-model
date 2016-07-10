<?php namespace Datashaman\Elasticsearch\Model;

use Illuminate\Support\Manager;

class DriverManager extends Manager
{
    private $response;
    private $options;

    public function __construct($response, $options=[], callable $callable=null)
    {
        parent::__construct(null);
        $this->response = $response;
        $this->options = $options;
        $this->callable = $callable;
    }

    public function getDefaultDriver()
    {
        return 'eloquent';
    }

    public function createEloquentDriver()
    {
        return new Driver\EloquentDriver($this->response, $this->options, $this->callable);
    }
}
