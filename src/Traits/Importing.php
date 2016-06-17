<?php namespace Datashaman\ElasticModel\Traits;

trait Importing
{
    protected static function _chunkToBulk($chunk, $_index, $_type)
    {
        $rows = $chunk->reduce(function ($carry, $item) use ($_index, $_type) {
            $_id = $item->id;
            $carry[] = [ 'index' => compact('_index', '_type', '_id') ];
            $carry[] = $item->toIndexArray();
            return $carry;
        }, []);
        return $rows;
    }

    public static function import($options=[])
    {
        $chunkSize = array_get($options, 'chunkSize', 1000);

        $index = array_get($options, 'index', static::$indexName);
        $type = array_get($options, 'type', static::$documentType);

        static::chunk($chunkSize, function ($chunk) use ($index, $type) {
            $body = static::_chunkToBulk($chunk, $index, $type);
            static::client()->bulk(compact('body'));
        });
    }
}
