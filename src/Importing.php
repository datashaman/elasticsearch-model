<?php namespace Datashaman\ElasticModel;

use Closure;
use Exception;

trait Importing
{
    protected static function _chunkToBulk($chunk, $transform=null)
    {
        $rows = $chunk->reduce(function ($carry, $item) {
            $_id = $item->id;
            $carry[] = [ 'index' => compact('_id') ];
            $carry[] = $item->toIndexedArray();
            return $carry;
        }, []);
        return $rows;
    }

    protected static function _transform()
    {
        return function ($model) {
            return [
                'index' => [
                    '_id' => $model->id,
                    'data' => $model->toIndexedArray(),
                ]
            ];
        };
    }

    public static function import($options=[], Closure $closure=null)
    {
        $errors = [];

        $chunkSize = array_pull($options, 'chunkSize', 1000);
        $refresh = array_pull($options, 'refresh', false);
        $targetIndex = array_pull($options, 'index', static::indexName());
        $targetType = array_pull($options, 'type', static::documentType());
        $transform = array_pull($options, 'transform', [static::class, '_transform']);
        $returnValue = array_pull($options, 'return', 'count');

        if (!is_callable($transform)) {
            throw new Exception(sprintf('Pass a callable as the transform option, %s given', $transform));
        }

        if (array_pull($options, 'force')) {
            static::createIndex(['force' => true, 'index' => $targetIndex]);
        } elseif (!static::indexExists(['index' => $targetIndex])) {
            throw new Exception(sprintf("%s does not exist to be imported into. Use createIndex() or the 'force' option to create it.", $targetIndex));
        }

        static::chunk($chunkSize, function ($chunk) use ($targetIndex, $targetType, $closure, &$errors) {
            $client = static::client();

            $response = $client->bulk([
                'index' => $targetIndex,
                'type' => $targetType,
                'body' => static::_chunkToBulk($chunk, $targetIndex, $targetType),
            ]);

            if (!is_null($closure)) {
                call_user_func($closure, $response);
            }

            $errors += array_values(array_filter($response['items'], function ($item) {
                $firstValue = head(array_values($item));
                return array_key_exists('error', $firstValue);
            }));
        });

        if ($refresh) {
            static::refreshIndex();
        }

        switch ($returnValue) {
        case 'errors':
            return $errors;
        default:
            return count($errors);
        }
    }
}
