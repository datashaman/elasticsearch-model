<?php

namespace Datashaman\Elasticsearch\Model;

trait ArrayDelegate
{
    public function offsetSet(mixed $offset, mixed $value): void
    {
        array_set($this->{static::$arrayDelegate}, $offset, $value);
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_has($this->{static::$arrayDelegate}, $offset);
    }

    public function offsetUnset(mixed $offset): void
    {
        array_forget($this->{static::$arrayDelegate}, $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return array_get($this->{static::$arrayDelegate}, $offset);
    }
}
