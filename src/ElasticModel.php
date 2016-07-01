<?php namespace Datashaman\ElasticModel;

use Illuminate\Support\Collection;

trait ElasticModel
{
    use Proxy;
    use Indexing;
    use Searching;
    // use Serializing;
    use Importing;

    public static function resetElasticModel()
    {
        static::$elasticsearch = null;
        static::$mapping = null;
        static::$settings = null;
    }
}
