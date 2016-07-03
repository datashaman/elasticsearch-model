<?php namespace Datashaman\ElasticModel;

use ArrayAccess;
use Illuminate\Support\Collection;

class Response extends GetOrSet implements ArrayAccess
{
    use ArrayDelegate;
    use Response\Pagination;

    protected static $arrayDelegate = 'results';
    public $class;

    public function __construct($class, $search, $response=null)
    {
        $this->class = $class;
        parent::__construct(compact('search', 'response'));
    }

    public function __get($name)
    {
        if ($name == 'results') {
            return $this->results();
        }

        return parent::__get($name);
    }

    public function __call($name, $args)
    {
        if (count($args) > 0) {
            $this->attributes[$name] = $args[0];
            return $this;
        }

        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }

        switch ($name) {
        case 'response':
            return $this->search()->execute();
        case 'results':
            return new Response\Results($this->class, $this);
        case 'records':
            return new Response\Records($this->class, $this);
        case 'took':
            return $this->response()['took'];
        case 'timedOut':
            return $this->response()['timed_out'];
        case 'shards':
            return $this->response()['_shards'];
        case 'aggregations':
            return array_get($this->response(), 'aggregations');
        case 'suggestions':
            $response = $this->response();
            return array_has($response, 'suggest') ? new Response\Suggestions($response['suggest']) : null;
        case 'from':
            return $this->search()->definition['from'];
        case 'size':
            return $this->search()->definition['size'];
        case 'total':
            return $this->results()['total'];
        default:
            return parent::__call($name, $args);
        }
    }
}
