<?php namespace Datashaman\ElasticModel;

class Adapter
{
    protected static $default = Adapter\DefaultAdapter::class;
    protected static $adapters;

    protected $class;
    protected $records;
    protected $adapter;

    public function __construct($class, $records)
    {
        $this->class = $class;
        $this->records = $records;
    }

    public static function fromClass($class, $records)
    {
        return new static($class, $records);
    }

    public static function register($name, $condition)
    {
        static::adapters()->put($name, $condition);
    }

    public static function adapters()
    {
        $args = func_get_args();

        if (count($args) == 0) {
            if (!isset(static::$adapters)) {
                static::$adapters = collect();
            }

            return static::$adapters;
        }

        static::$adapters = collect($args[0]);
    }

    public function adapter()
    {
        if (!isset($this->adapter)) {
            $adapter = null;

            static::adapters()->each(function ($condition, $name) use (&$adapter) {
                if (call_user_func($condition, $this->class)) {
                    $adapter = $name;
                    return false;
                }
            });

            if (is_null($adapter) && property_exists(static::class, 'default')) {
                $adapter = static::$default;
            }

            $this->adapter = new $adapter($this->class, $this->records);
        }

        return $this->adapter;
    }

    public function records()
    {
        return $this->adapter()->records();
    }
}
