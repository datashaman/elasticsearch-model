<?php

namespace Datashaman\Elasticsearch\Model;

trait Callbacks
{
    public static function bootCallbacks()
    {
        static::created(function ($object) {
            $object->indexDocument();
        });

        static::updated(function ($object) {
            $object->updateDocument();
        });

        static::deleted(function ($object) {
            $object->deleteDocument();
        });
    }
}
