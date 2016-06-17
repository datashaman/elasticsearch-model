<?php namespace Datashaman\ElasticModel\Traits;

trait Naming
{
    protected static $documentType;
    protected static $indexName;

    /**
     * Get/set the document type
     */
    public static function documentType()
    {
        if (func_num_args() == 0) {
            if (!isset(static::$documentType)) {
                static::$documentType = strtolower(last(explode('\\', static::class)));
            }

            return static::$documentType;
        } else {
            static::$documentType = func_get_arg(0);
        }
    }

    /**
     * Get/set the index name
     */
    public static function indexName()
    {
        if (func_num_args() == 0) {
            if (!isset(static::$indexName)) {
                static::$indexName = str_plural(static::documentType());
            }

            return static::$indexName;
        } else {
            static::$indexName = func_get_arg(0);
        }
    }
}
