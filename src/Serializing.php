<?php namespace Datashaman\Elasticsearch\Model;

trait Serializing
{
    public function toIndexedArray()
    {
        return $this->toArray();
    }
}
