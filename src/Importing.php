<?php

/**
 * Provides support for easily and efficiently importing large amounts of
 * records from the including class into the index.
 */
namespace Datashaman\Elasticsearch\Model;

use Exception;
use Illuminate\Support\Collection;

/**
 * When used in a class, adds importing methods. Typically used by the elasticsearch proxy object.
 */
trait Importing
{
    /**
     * Convert a chunk (collection) of models into an Elasticsearch bulk API body.
     *
     * @param  \Illuminate\Support\Collection $chunk     Collection of models to be indexed.
     * @param  callable                       $transform Function which converts models to simplified bulk API format.
     * @return string
     */
    protected function chunkToBulk($chunk, callable $transform)
    {
        $bulk = $chunk
            ->map($transform)
            ->reduce(function ($bulk, $item) {
                foreach ($item as $action => $meta) {
                    $data = array_pull($meta, 'data');
                    $bulk[] = [$action => $meta];
                    $bulk[] = $data;
                }

                return $bulk;
            }, Collection::make())
            ->map(function ($row) {
                return json_encode($row);
            })
            ->implode("\n")."\n";

        return $bulk;
    }

    /**
     * Return a default transform function which transforms a model to the format used by the import method (simple bulk API).
     *
     * The callable receives one argument: the model to be transformed. It should return an array of the following form:
     *
     *     [action => ['_id' => id, 'data' => data]]
     *
     * Where action is typically *index*, but could also be *create*, *delete* or *update*. There is no need to specify *index* or *type* in the array, those are specified in the main request. The *delete* action does not require a *data* item.
     *
     * @return callable
     */
    public function transform()
    {
        return function ($model) {
            return [
                'index' => [
                    '_id' => $model->id,
                    'data' => $model->toIndexedArray(),
                ],
            ];
        };
    }

    /**
     * Import all model records into the index.
     *
     * The method will pick up correct strategy based on the driver manager.
     *
     * Import all records into the index
     *
     *     Article::elasticsearch()->import();
     *
     * Set the chunk size to 100
     *
     *     Article::elasticsearch()->import(['chunkSize' => 100]);
     *
     * Process the response from Elasticsearch
     *
     *     Article::elasticsearch()->import([], function ($response) {
     *       echo "Got " . $response->map(function ($i) { return $i['index']['error']; })->count() . " errors"
     *     });
     *
     * Delete and create the index with appropriate settings and mappings
     *
     *     Article::elasticsearch()->import(['force' => true]);
     *
     * Refresh the index after importing all chunks
     *
     *     Article::elasticsearch()->import(['refresh' => true]);
     *
     * Import the records into a different index/type than the default one
     *
     *     Article::elasticsearch()->import(['index' => 'my-new-name', 'type' => 'my-other-type']);
     *
     * Pass an Eloquent scope to limit the imported records
     *
     *     Article::elasticsearch()->import(['scope' => 'published']);
     *
     * Pass a query callable to alter the query used
     *
     *     Article::elasticsearch()->import(['query' => function ($q) {
     *         $q->where('author_id', author_id);
     *     });
     *
     * Transform records during the import with a callable
     *
     *     $transform = function ($a) {
     *         return [
     *             'index' => [
     *                 '_id' => $a->id,
     *                 '_parent' => $a->author_id,
     *                 'data' => $a->toIndexedArray(),
     *             ],
     *         ];
     *     };
     *
     *     Article::elasticsearch()->import(['transform' => $transform]);
     *
     * Update the chunk before importing it:
     *
     *      class Article
     *      {
     *        ...
     *        public static function enrich($chunk)
     *        {
     *          return $chunk->map(function ($item) {
     *            $item->metadata = MyAPI::get_metadata($item->id);
     *            return $item;
     *          });
     *        }
     *        ...
     *      }
     *
     *      Article::elasticsearch()->import(['preprocess' => 'enrich']);
     *
     * Return an array of error elements instead of the number of errors, eg.
     *          to try importing these records again
     *
     *      Article::elasticsearch()->import(['return' => 'errors']);
     *
     * @param array $options Options used during the import process
     *
     * * boolean  *force*     Create the index while importing if it does not exist. Recreate it if it does.
     * * integer  *chunkSize* Size of the chunk when importing. Default *1000*.
     * * boolean  *refresh*   Refresh the index after importing. Default *false*.
     * * string   *index*     Use a custom index name. Defaults to *indexName* of the model class.
     * * string   *type*      Use a custom document type. Defaults to *documentType* of the model class.
     * * callable *transform* Custom transformation of records into bulk API format. Defaults this classes' *transform* method.
     * * string   *return*    Return *count* or *errors* from the import method. Default *count*.
     *
     * @param callable $callable Optional callable to process response
     *
     * @return mixed Returns count of errors by default, can be configured to return array of errors. Use *return* in options to configure this.
     */
    public function import($options = [], callable $callable = null)
    {
        $errors = [];

        $refresh = array_pull($options, 'refresh', false);
        $targetIndex = array_pull($options, 'index', $this->indexName());
        $targetType = array_pull($options, 'type', $this->documentType());
        $transform = array_pull($options, 'transform', [$this, 'transform']);
        $returnValue = array_pull($options, 'return', 'count');

        if (! is_callable($transform)) {
            throw new Exception(sprintf('Pass a callable as the transform option, %s given', $transform));
        }

        if (array_pull($options, 'force')) {
            $this->createIndex(['force' => true, 'index' => $targetIndex]);
        } elseif (! $this->indexExists(['index' => $targetIndex])) {
            throw new Exception(sprintf("%s does not exist to be imported into. Use createIndex() or the 'force' option to create it.", $targetIndex));
        }

        $this->findInChunks($options, function ($chunk) use ($targetIndex, $targetType, $transform, $callable, &$errors) {
            $args = [
                'index' => $targetIndex,
                'type' => $targetType,
                'body' => $this->chunkToBulk($chunk, call_user_func($transform)),
            ];

            $response = $this->client()->bulk($args);

            if (is_callable($callable)) {
                call_user_func($callable, $response);
            }

            $errors += array_values(array_filter($response['items'], function ($item) {
                $firstValue = head(array_values($item));

                return array_key_exists('error', $firstValue);
            }));
        });

        if ($refresh) {
            $this->refreshIndex();
        }

        switch ($returnValue) {
        case 'errors':
            return $errors;
        default:
            return count($errors);
        }
    }
}
