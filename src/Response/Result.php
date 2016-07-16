<?php

namespace Datashaman\Elasticsearch\Model\Response;

class Result
{
    protected $hit;

    public function __construct($hit = [])
    {
        $this->hit = $hit;
    }

    public function __get($name)
    {
        if (in_array($name, ['index', 'type', 'id', 'score', 'source'])) {
            return array_get($this->hit, '_'.$name);
        }

        if (array_has($this->hit, $name)) {
            return array_get($this->hit, $name);
        }

        if (array_has($this->hit, '_source')
            && array_has($this->hit['_source'], $name)) {
            return array_get($this->hit['_source'], $name);
        }

        $trace = debug_backtrace();

        trigger_error(
            'Undefined property via __get(): '.$name.
            ' in '.$trace[0]['file'].
            ' on line '.$trace[0]['line'],
            E_USER_NOTICE);
    }

    public function toArray()
    {
        return $this->hit;
    }
}
