<?php namespace Datashaman\ElasticModel\Response;

use ArrayAccess;
use Datashaman\ElasticModel\Adapter;
use Datashaman\ElasticModel\ArrayDelegate;
use Illuminate\Support\Collection;


class Records extends Base implements ArrayAccess
{
    use ArrayDelegate;

    protected static $arrayDelegate = 'records';
    protected $options;

    public function __construct($class, $response, $options=[])
    {
        parent::__construct($class, $response);
        $this->adapter = Adapter::fromClass($class, $this);
        $this->options = $options;
    }

    public function __get($name)
    {
        switch ($name) {
        case 'ids':
            return $this->response->results->map(function ($hit) {
                return $hit->id;
            });
        case 'results':
            return $this->response->results;
        case 'records':
            $records = $this->adapter->records();
            return $records;
        default:
            return parent::__get($name);
        }
    }

    public function __call($name, $args)
    {
        return call_user_func_array([ $this->records, $name ], $args);
    }

    private function zip($first, $second)
    {
        /** Collection::zip produces incorrect results, believe it or not */
        $zipped = [];

        $first->each(function ($item, $key) use ($second, &$zipped) {
            $zipped[] = [ $item, $second[$key] ];
        });

        return collect($zipped);
    }

    public function eachWithHit(callable $callable)
    {
        $collection = $this->zip($this->records, $this->results->results);
        return $collection->each(function ($both) use ($callable) {
            call_user_func($callable, $both[0], $both[1]);
        });
    }

    public function mapWithHit(callable $callable)
    {
        $collection = $this->zip($this->records, $this->results->results);
        return $collection->map(function ($both) use ($callable) {
            return call_user_func($callable, $both[0], $both[1]);
        });
    }
}
