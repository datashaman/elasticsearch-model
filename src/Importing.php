<?php namespace Datashaman\ElasticModel;

use Exception;
use Illuminate\Support\Collection;

trait Importing
{
    protected static function transform()
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

    protected static function chunkToBulk($chunk, callable $transform)
    {
        $bulk = $chunk
            ->map($transform)
            ->reduce(function ($bulk, $item) {
                foreach($item as $action => $meta) {
                    $data = array_pull($meta, 'data');
                    $bulk[] = [ $action => $meta ];
                    $bulk[] = $data;
                }
                return $bulk;
            }, new Collection)
            ->map('json_encode')
            ->implode("\n") . "\n";
        return $bulk;
    }

    public static function import($options=[], callable $callable=null)
    {
        $errors = [];

        $chunkSize = array_pull($options, 'chunkSize', 1000);
        $refresh = array_pull($options, 'refresh', false);
        $targetIndex = array_pull($options, 'index', static::elastic()->indexName);
        $targetType = array_pull($options, 'type', static::elastic()->documentType);
        $transform = array_pull($options, 'transform', [static::class, 'transform']);
        $returnValue = array_pull($options, 'return', 'count');
        $wait = array_pull($options, 'wait', false);

        if (!is_callable($transform)) {
            throw new Exception(sprintf('Pass a callable as the transform option, %s given', $transform));
        }

        if (array_pull($options, 'force')) {
            static::createIndex(['force' => true, 'index' => $targetIndex]);
        } elseif (!static::indexExists(['index' => $targetIndex])) {
            throw new Exception(sprintf("%s does not exist to be imported into. Use createIndex() or the 'force' option to create it.", $targetIndex));
        }

        static::chunk($chunkSize, function ($chunk) use ($targetIndex, $targetType, $transform, $callable, &$errors, $wait) {
            $args = [
                'index' => $targetIndex,
                'type' => $targetType,
                'body' => static::chunkToBulk($chunk, call_user_func($transform)),
            ];

            if ($wait) {
                $args['client'] = [ 'future' => 'lazy' ];
            }

            $response = static::elastic()->client->bulk($args);

            if (is_callable($callable)) {
                call_user_func($callable, $response);
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
