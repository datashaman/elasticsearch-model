<?php

namespace Datashaman\Elasticsearch\Model\Response;

trait Delegates
{
    /**
     * Delegate methods to the results paginator
     */
    public function __call($name, $args)
    {
        return call_user_func_array([$this->results(), $name], $args);
    }

    public function from()
    {
        return array_get($this->search->definition, 'from');
    }

    public function size()
    {
        return array_get($this->search->definition, 'size');
    }

    public function took()
    {
        return array_get($this->response(), 'took');
    }

    public function timedOut()
    {
        return array_get($this->response(), 'timed_out');
    }

    public function shards()
    {
        return array_get($this->response(), '_shards');
    }

    public function aggregations()
    {
        return array_get($this->response(), 'aggregations');
    }

    public function total()
    {
        return array_get($this->response(), 'hits.total');
    }
}
