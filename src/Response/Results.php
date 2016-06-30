<?php namespace Datashaman\ElasticModel\Response;

use ArrayAccess;
use Datashaman\ElasticModel\ArrayDelegate;

class Results extends Base implements ArrayAccess
{
    use ArrayDelegate;

    protected static $arrayDelegate = 'results';

    public function __get($name)
    {
        switch ($name) {
        case 'results':
            return array_map(function ($hit) {
                return new Result($hit);
            }, $this->response->response['hits']['hits']);
        }
    }
}
