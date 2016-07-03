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
        case 'id':
        case 'type':
            return array_get($this->result, '_'.$name);
        default:
            if(array_has($this->result, $name)) {
                return array_get($this->result, $name);
            }

            if (array_has($this->result, '_source')
                && array_has($this->result['_source'], $name)) {
                return array_get($this->result['_source'], $name);
            }
        }
    }

    public function toArray()
    {
        return $this->result;
    }
}
