<?php namespace Datashaman\Elasticsearch\Model;

trait ArrayDelegate
{
    public function offsetSet($offset, $value) {
        array_set($this->{static::$arrayDelegate}, $offset, $value);
    }

    public function offsetExists($offset) {
        return array_has($this->{static::$arrayDelegate}, $offset);
    }

    public function offsetUnset($offset) {
        array_forget($this->{static::$arrayDelegate}, $offset);
    }

    public function offsetGet($offset) {
        return array_get($this->{static::$arrayDelegate}, $offset);
    }
}
