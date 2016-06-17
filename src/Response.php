<?php namespace Datashaman\ElasticModel;

use ArrayAccess;
use ArrayObject;

function setOrCreate($value, $creator)
{
    if (!isset($value)) {
        $value = $creator();
    }

    return $value;
}

class Response implements ArrayAccess
{
    use Traits\ArrayDelegate;

    protected $_arrayDelegate = 'results';

    protected $class;
    protected $search;

    protected $_response;
    protected $_results;
    protected $_records;

    public function __construct($class, $search)
    {
        $this->class = $class;
        $this->search = $search;
    }

    public function records($options=[])
    {
        return new Response\Records($this->class, $this, $options);
    }

    public function __get($name)
    {
        switch ($name) {
        case 'response':
            return setOrCreate($this->_response, function () {
                return new ArrayObject($this->search->execute());
            });
        case 'results':
            return setOrCreate($this->_results, function () {
                return new Response\Results($this->class, $this);
            });
        case 'records':
            return setOrCreate($this->_records, function () {
                return $this->records();
            });
        case 'took':
            return $this->response['took'];
        case 'timedOut':
            return $this->response['timed_out'];
        case 'shards':
            return new ArrayObject($this->response['_shards']);
        case 'aggregations':
            return array_has($this->response, 'aggregations') ? new ArrayObject(array_get($this->response, 'aggregations')) : null;
        case 'suggestions':
            return array_has($this->response, 'suggest') ? new Response\Suggestions($this->response['suggest'], ArrayObject::STD_PROP_LIST) : null;
        }
    }
}
