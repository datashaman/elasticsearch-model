<?php namespace Datashaman\ElasticModel;

use ArrayAccess;
use Illuminate\Support\Collection;
use Illuminate\Support\Fluent;

class Response implements ArrayAccess
{
    use ArrayDelegate;
    use Response\Pagination;

    protected static $arrayDelegate = 'results';
    protected $attributes;

    public function __construct($class, $search, $response=null)
    {
        $this->attributes = new Fluent(compact('class', 'search', 'response'));
    }

    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    public function __get($name)
    {
        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }

        switch ($name) {
        case 'response':
            return $this->search->execute();
        case 'results':
            return new Response\Results($this->class, $this);
        case 'records':
            return new Response\Records($this->class, $this);
        case 'took':
            return $this->response['took'];
        case 'timedOut':
            return $this->response['timed_out'];
        case 'shards':
            return $this->response['_shards'];
        case 'aggregations':
            return array_get($this->response, 'aggregations');
        case 'suggestions':
            return array_has($this->response, 'suggest') ? new Response\Suggestions($this->response['suggest']) : null;
        case 'from':
            return $this->search->definition['from'];
        case 'size':
            return $this->search->definition['size'];
        case 'total':
            return $this->results['total'];
        default:
            parent::__get($name);
        }
    }
}
