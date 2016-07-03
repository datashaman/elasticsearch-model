<?php namespace Datashaman\ElasticModel;

trait ArrayDelegate
{
    private function &resolveArray()
    {
        return $this->{static::$arrayDelegate};
    }

    public function offsetSet($offset, $value) {
        $array = &$this->resolveArray();
        array_set($array, $offset, $value);
    }

    public function offsetExists($offset) {
        $array = &$this->resolveArray();
        return array_has($array, $offset);
    }

    public function offsetUnset($offset) {
        $array = &$this->resolveArray();
        array_forget($array, $offset);
    }

    public function offsetGet($offset) {
        $array = &$this->resolveArray();
        return array_get($array, $offset);
    }
}
