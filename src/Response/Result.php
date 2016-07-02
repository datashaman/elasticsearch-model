<?php namespace Datashaman\ElasticModel\Response;

use ArrayObject;

class Result
{
    protected $result;

    public function __construct($attributes=[])
    {
        $this->result = $attributes;
    }

    public function __get($name)
    {
        switch ($name) {
        case 'index':
        case 'type':
        case 'id':
        case 'score':
        case 'source':
            return $this->result['_'.$name];
        }
    }

    public function toArray()
    {
        return $this->result;
    }

    /** TODO: Implement delegate methods to result or result._source */
}
