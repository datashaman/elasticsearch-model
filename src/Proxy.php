<?php namespace Datashaman\ElasticModel;

use Illuminate\Support\Collection;

trait Proxy
{
    protected static $elasticStatic;
    protected $elasticInstance;

    public static function getOrSetStatic($args, $key, $default)
    {
        $collection = static::getElasticStatic();

        if (count($args) == 0) {
            if (!$collection->has($key)) {
                $collection->put($key, $default);
            }

            return $collection->get($key);
        }

        $collection->put($key, head($args));
    }

    protected static function getElasticStatic()
    {
        if (!isset(static::$elasticStatic)) {
            static::$elasticStatic = new Collection;
        }

        return static::$elasticStatic;
    }

    protected function getElasticInstance()
    {
        if (!isset($this->elasticStatic)) {
            $this->elasticStatic = new Collection;
        }

        return $this->elasticStatic;
    }
}
