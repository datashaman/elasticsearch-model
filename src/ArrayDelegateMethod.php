<?php

namespace Datashaman\Elasticsearch\Model;

use Exception;

trait ArrayDelegateMethod
{
    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new Exception('Not implemented');
    }

    public function offsetExists(mixed $offset): bool
    {
        $array = call_user_func([$this, static::$arrayDelegate]);

        return isset($array[$offset]);
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new Exception('Not implemented');
    }

    public function offsetGet(mixed $offset): mixed
    {
        $array = call_user_func([$this, static::$arrayDelegate]);
        $item = $array[$offset];

        return $item;
    }

    public function count(): int
    {
        $array = call_user_func([$this, static::$arrayDelegate]);

        return count($array);
    }
}
