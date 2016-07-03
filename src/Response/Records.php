<?php namespace Datashaman\ElasticModel\Response;

use ArrayAccess;
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
            /** TODO: Abstract this so more than Eloquent can be used */
            $class = $this->class;
            $records = $class::whereIn('id', $this->ids)->get();

            $ordered = [];

            foreach ($this->ids as $id) {
                foreach ($records as $index => $record) {
                    if ($record->id == $id) {
                        $ordered[] = $record;
                        array_pull($records, $index);
                        break;
                    }
                }
            }

            return Collection::make($ordered);
        default:
            return parent::__get($name);
        }
    }

    public function __call($name, $args)
    {
        return call_user_func_array([ $this->records, $name ], $args);
    }

    public function eachWithHit(callable $callable)
    {
        $collection = $this->records->zip($this->results->results);
        return $collection->each(function ($both) use ($callable) {
            call_user_func($callable, $both[0], $both[1]);
        });
    }

    public function mapWithHit(callable $callable)
    {
        $collection = $this->records->zip($this->results->results);
        return $collection->map(function ($both) use ($callable) {
            return call_user_func($callable, $both[0], $both[1]);
        });
    }
}
