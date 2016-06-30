<?php namespace Datashaman\ElasticModel;

use ArrayAccess;
use ArrayObject;
use Illuminate\Support\Collection;

class Response implements ArrayAccess
{
    use ArrayDelegate;

    protected static $arrayDelegate = 'results';

    public $class;
    public $search;

    protected $attributes;

    public function __construct($class, $search)
    {
        $this->class = $class;
        $this->search = $search;
        $this->attributes = new Collection;
    }

    public function getRecords($options=[])
    {
        return new Response\Records($this->class, $this, $options);
    }

    private function setOrCreate($key, callable $creator)
    {
        if (!$this->attributes->has($key)) {
            $this->attributes->put($key, call_user_func($creator));
        }
        return $this->attributes->get($key);
    }

    public function __get($name)
    {
        switch ($name) {
        case 'response':
            return $this->setOrCreate('response', function () {
                return new ArrayObject($this->search->execute(), ArrayObject::ARRAY_AS_PROPS);
            });
        case 'results':
            return $this->setOrCreate('results', function () {
                return new Response\Results($this->class, $this);
            });
        case 'records':
            return $this->setOrCreate('records', function () {
                return $this->getRecords();
            });
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
