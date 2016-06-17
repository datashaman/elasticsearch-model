<?php namespace Datashaman\ElasticModel\Response;

class Base
{
    protected $class;
    protected $response;

    public function __construct($class, $response)
    {
        $this->class = $class;
        $this->response = $response;
    }

    public function __get($name)
    {
        switch ($name) {
        case 'results':
        case 'response':
            throw new Exception('Implement this method in '.$this->class);
        case 'total':
            return $this->response->response['hits']['total'];
        case 'max_score':
            return $this->response->response['hits']['max_score'];
        }
    }
}
