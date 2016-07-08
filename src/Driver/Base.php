<?php namespace Datashaman\ElasticModel\Driver;

class Base
{
    public function __construct($response)
    {
        $this->response = $response;
    }
}
