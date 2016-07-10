<?php namespace Datashaman\Elasticsearch\Model\Response;

use ArrayAccess;
use Datashaman\Elasticsearch\Model\DriverManager;
use Datashaman\Elasticsearch\Model\ArrayDelegate;
use Illuminate\Support\Collection;

class Records implements ArrayAccess
{
    use ArrayDelegate;
    protected static $arrayDelegate = 'records';

    protected $response;
    protected $options;
    protected $callable;

    public function __construct($response, $options=[], callable $callable=null)
    {
        $this->response = $response;
        $this->options = $options;
        $this->callable = $callable;
        $this->driverManager = new DriverManager($response, $options, $callable);
        $this->records = $this->driverManager->records();
    }

    public function __call($name, $args)
    {
        return call_user_func_array([$this->records, $name], $args);
    }

    public function realZip($second)
    {
        /** Collection::zip produces incorrect results, believe it or not */
        $zipped = collect();

        $this->records->each(function ($item, $key) use ($second, &$zipped) {
            $zipped->push([ $item, $second[$key] ]);
        });

        return $zipped;
    }

    public function eachWithHit(callable $callable)
    {
        $collection = $this->realZip($this->response);
        return $collection->each(function ($both) use ($callable) {
            call_user_func($callable, $both[0], $both[1]);
        });
    }

    public function mapWithHit(callable $callable)
    {
        $collection = $this->realZip($this->response);
        return $collection->map(function ($both) use ($callable) {
            return call_user_func($callable, $both[0], $both[1]);
        });
    }
}
