<?php namespace Datashaman\ElasticModel\Response;

use ArrayObject;

class Result
{
    protected $result;

    public function __construct($attributes=[])
    {
        $this->result = new ArrayObject($attributes, ArrayObject::ARRAY_AS_PROPS);
    }

    public function __get($name)
    {
        switch ($name) {
        case 'index':
        case 'type':
        case 'id':
        case 'score':
        case 'source':
            return $this->result->{'_'.$name};
        }
    }

    /** TODO: Implement delegate methods to result or result._source */
}
