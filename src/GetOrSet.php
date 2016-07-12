<?php

namespace Datashaman\Elasticsearch\Model;

class GetOrSet
{
    protected $attributes;

    public function __construct($attributes = [])
    {
        $this->attributes = $attributes;
    }

    public function __call($name, $args)
    {
        if (count($args) == 0) {
            return array_get($this->attributes, $name);
        }

        $this->attributes[$name] = $args[0];

        return $this;
    }
}
