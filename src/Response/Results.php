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
            $results = collect($this->response->response()['hits']['hits'])
                ->map(function ($hit) {
                    return new Result($hit);
                });
            return $results;
        default:
            return parent::__get($name);
        }
    }

    public function total()
    {
        $total = $this->response->response()['hits']['total'];
        return $total;
    }

    public function __call($name, $args) {
        return call_user_func_array([ $this->results, $name ], $args);
    }
}
