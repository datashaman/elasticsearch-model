<?php namespace Datashaman\ElasticModel\Traits;

trait Serializing
{
    public function toIndexedArray()
    {
        return $this->toArray();
    }
}
