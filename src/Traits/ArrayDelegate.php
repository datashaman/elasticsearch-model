<?php namespace Datashaman\ElasticModel\Traits;

trait ArrayDelegate
{
    public function offsetSet($offset, $value) {
        array_set($this->{$this->_arrayDelegate}, $offset, $value);
    }

    public function offsetExists($offset) {
        return array_has($this->{$this->_arrayDelegate}, $offset);
    }

    public function offsetUnset($offset) {
        array_forget($this->{$this->_arrayDelegate}, $offset);
    }

    public function offsetGet($offset) {
        return array_get($this->{$this->_arrayDelegate}, $offset);
    }
}
