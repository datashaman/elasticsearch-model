<?php namespace Datashaman\ElasticModel\Traits;


trait ElasticModel
{
    // use Naming;
    use Indexing;
    use Searching;
    // use Serializing;
    // use Importing;

    public static function resetElasticModel()
    {
        static::documentType(null);
        static::indexName(null);
        static::$mapping = null;
        static::$settings = null;
    }
}
