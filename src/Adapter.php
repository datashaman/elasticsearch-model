<?php namespace Datashaman\ElasticModel;

class Adapter
{
    protected static $adapters;

    protected $adapter;

    public function __construct($class)
    {
        $this->class = $class;
    }

    public static function fromClass($class)
    {
        return new static($class);
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

            $this->adapter = $adapter;
        }

        return $this->adapter;
    }
}
