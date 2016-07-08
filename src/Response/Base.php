<?php namespace Datashaman\ElasticModel\Response;

use Illuminate\Support\Collection;

class Base extends Collection
{
    protected $class;
    protected $response;

    public function __construct($class, $response)
    {
        parent::__construct();

        $this->class = $class;
        $this->response = $response;
    }

    public function __get($name)
    {
        switch ($name) {
        case 'results':
            throw new Exception('Implement this method in '.$this->class);
        }
    }
}
