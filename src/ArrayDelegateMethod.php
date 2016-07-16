<?php

namespace Datashaman\Elasticsearch\Model;

trait ArrayDelegateMethod
{
    public function offsetSet($offset, $value)
    {
        array_set(call_user_func([$this, static::$arrayDelegate]), $offset, $value);
    }

    public function offsetExists($offset)
    {
        return array_has(call_user_func([$this, static::$arrayDelegate]), $offset);
    }

    public function offsetUnset($offset)
    {
        array_forget(call_user_func([$this, static::$arrayDelegate]), $offset);
    }

    public function offsetGet($offset)
    {
        return array_get(call_user_func([$this, static::$arrayDelegate]), $offset);
    }
}
