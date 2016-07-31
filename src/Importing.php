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
     * The method will pick up correct strategy based on the `Importing` module
     * defined in the corresponding adapter.
     *
     * Import all records into the index
     *
     *     Article::elasticsearch()->import();
     *
     * Set the batch size to 100
     *
     *     Article::elasticsearch()->import(['batch_size' => 100]);
     *
     * Process the response from Elasticsearch
     *
     *     Article::elasticsearch()->import([], function ($response) {
     *       echo "Got " . $response->map(function ($i) { return $i['index']['error'] })->count() . " errors"
     *     });
     *
     * Delete and create the index with appropriate settings and mappings
     *
     *    Article::elasticsearch()->import(['force' => true]);
     *
     * Refresh the index after importing all batches
     *
     *    Article::elasticsearch()->import(['refresh' => true]);
     *
     * Import the records into a different index/type than the default one
     *
     *    Article::elasticsearch()->import(['index' => 'my-new-name', 'type' => 'my-other-type']);
     *
     * Pass an Eloquent scope to limit the imported records
     *
     *    Article::elasticsearch()->import(['scope' => 'published']);
     *
     * Pass an ActiveRecord query to limit the imported records
     *
     *    Article.import query: -> { where(author_id: author_id) }
     *
     * Transform records during the import with a lambda
     *
     *    transform = lambda do |a|
     *      {index: {_id: a.id, _parent: a.author_id, data: a.__elasticsearch__.as_indexed_json}}
     *    end
     *
     *    Article.import transform: transform
     *
     * Update the batch before yielding it
     *
     *     class Article
     *       # ...
     *       def self.enrich(batch)
     *         batch.each do |item|
     *           item.metadata = MyAPI.get_metadata(item.id)
     *         end
     *         batch
     *       end
     *     end
     *
     *    Article.import preprocess: :enrich
     *
     * Return an array of error elements instead of the number of errors, eg.
     *          to try importing these records again
     *
     *    Article.import return: 'errors'
     */
    public function import($options = [], callable $callable = null)
    {
        $errors = [];

        $refresh = array_pull($options, 'refresh', false);
        $targetIndex = array_pull($options, 'index', $this->indexName());
        $targetType = array_pull($options, 'type', $this->documentType());
        $transform = array_pull($options, 'transform', [$this, 'transform']);
        $returnValue = array_pull($options, 'return', 'count');
        $wait = array_pull($options, 'wait', false);

        if (! is_callable($transform)) {
            throw new Exception(sprintf('Pass a callable as the transform option, %s given', $transform));
        }

        if (array_pull($options, 'force')) {
            $this->createIndex(['force' => true, 'index' => $targetIndex]);
        } elseif (! $this->indexExists(['index' => $targetIndex])) {
            throw new Exception(sprintf("%s does not exist to be imported into. Use createIndex() or the 'force' option to create it.", $targetIndex));
        }

        $this->driverManager->findInBatches($options, function ($chunk) use ($targetIndex, $targetType, $transform, $callable, &$errors, $wait) {
            $args = [
                'index' => $targetIndex,
                'type' => $targetType,
                'body' => $this->chunkToBulk($chunk, call_user_func($transform)),
            ];

            if ($wait) {
                $args['client'] = ['future' => 'lazy'];
            }

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
