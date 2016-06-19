<?php namespace Datashaman\ElasticModel;

trait Serializing
{
    public function toIndexedArray()
    {
        return $this->toArray();
    }
}
