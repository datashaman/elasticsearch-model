<?php namespace Datashaman\ElasticModel;

use ArrayAccess;
use ArrayObject;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class Response extends LengthAwarePaginator implements ArrayAccess
{
    use ArrayDelegate;
    use Response\Pagination;

    protected static $arrayDelegate = 'results';

    public $class;
    public $search;

    protected $attributes;

    public function __construct($class, $search, $response=null)
    {
        $this->class = $class;
        $this->search = $search;
        $this->attributes = Collection::make();
        if (!is_null($response)) {
            $this->attributes->put('response', $response);
        }
    }

    public function __get($name)
    {
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
            return new ArrayObject($this->response['_shards'], ArrayObject::ARRAY_AS_PROPS);
        case 'aggregations':
            return array_has($this->response, 'aggregations') ? new ArrayObject(array_get($this->response, 'aggregations'), ArrayObject::ARRAY_AS_PROPS) : null;
        case 'suggestions':
            return array_has($this->response, 'suggest') ? new Response\Suggestions($this->response['suggest']) : null;
        }
    }
}
