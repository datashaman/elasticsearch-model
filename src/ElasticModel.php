<?php namespace Datashaman\ElasticModel;

use Illuminate\Support\Collection;

trait ElasticModel
{
    use Proxy;
    // use Naming;
    use Indexing;
    use Searching;
    // use Serializing;
    use Importing;

    public static function resetElasticModel()
    {
        static::$elasticStatic = new Collection;
        static::$mapping = null;
        static::$settings = null;
    }
}
