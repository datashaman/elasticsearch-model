<?php namespace Datashaman\ElasticModel\Driver;

class Base
{
    public $response;
    public $options;

    public function __construct($response, $options=[])
    {
        $this->response = $response;
        $this->options = $options;
    }
}
