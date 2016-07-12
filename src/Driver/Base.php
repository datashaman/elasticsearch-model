<?php

namespace Datashaman\Elasticsearch\Model\Driver;

class Base
{
    public $response;
    public $options;
    public $callable;

    public function __construct($response, $options = [], callable $callable = null)
    {
        $this->response = $response;
        $this->options = $options;
        $this->callable = $callable;
    }
}
