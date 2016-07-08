<?php namespace Datashaman\ElasticModel\Response;

use ArrayObject;

class Result
{
    protected $hit;

    public function __construct($hit=[])
    {
        $this->hit = $hit;
    }

    public function __get($name)
    {
        switch ($name) {
        case 'id':
        case 'type':
            return array_get($this->hit, '_'.$name);
        default:
            if(array_has($this->hit, $name)) {
                return array_get($this->hit, $name);
            }

            if (array_has($this->hit, '_source')
                && array_has($this->hit['_source'], $name)) {
                return array_get($this->hit['_source'], $name);
            }
        }
    }
}
