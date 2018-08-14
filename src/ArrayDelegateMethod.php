<?php

namespace Datashaman\Elasticsearch\Model;

use Exception;

trait ArrayDelegateMethod
{
    public function offsetSet($offset, $value)
    {
        throw new Exception('Not implemented');
    }

    public function offsetExists($offset)
    {
        $array = call_user_func([$this, static::$arrayDelegate]);

        return isset($array[$offset]);
    }

    public function offsetUnset($offset)
    {
        throw new Exception('Not implemented');
    }

    public function offsetGet($offset)
    {
        $array = call_user_func([$this, static::$arrayDelegate]);
        $item = $array[$offset];

        return $item;
    }

    public function count()
    {
        $array = call_user_func([$this, static::$arrayDelegate]);

        return count($array);
    }
}
