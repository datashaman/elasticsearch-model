<?php namespace Datashaman\ElasticModel;

trait Naming
{
    /**
     * Get/put the document type
     */
    public static function documentType()
    {
        return static::getOrSetStatic(func_get_args(), 'documentType', str_slug(last(explode('\\', static::class))));
    }

    /**
     * Get/put the index name
     */
    public static function indexName()
    {
        return static::getOrSetStatic(func_get_args(), 'indexName', str_plural(static::documentType()));
    }
}
