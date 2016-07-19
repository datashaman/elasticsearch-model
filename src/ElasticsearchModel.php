<?php

namespace Datashaman\Elasticsearch\Model;

use Elasticsearch\ClientBuilder;

class Elasticsearch extends GetOrSet
{
    use Searching;
    use Importing;
    use Indexing;

    protected $class;

    public function __construct($class)
    {
        $name = preg_replace('/([A-Z])/', ' \1', class_basename($class));

        $attributes = [
            'client' => ClientBuilder::create()->build(),
            'documentType' => isset($class::$documentType) ? $class::$documentType : str_slug($name),
            'indexName' => isset($class::$indexName) ? $class::$indexName : str_slug(str_plural($name)),
        ];

        parent::__construct($attributes);

        $this->class = $class;
    }
}

trait ElasticsearchModel
{
    use Serializing;
    use Importing;

    public static function resetElasticsearch()
    {
        static::$elasticsearch = null;
    }

    public static function elasticsearch()
    {
        $args = func_get_args();

        if (count($args) == 0) {
            if (empty(static::$elasticsearch)) {
                static::$elasticsearch = new Elasticsearch(static::class);
            }

            return static::$elasticsearch;
        }

        static::$elasticsearch = $args[0];

        return static::$elasticsearch;
    }

    public static function search($query, $options = [])
    {
        return static::elasticsearch()->search($query, $options);
    }

    public static function mappings($options = [], callable $callable = null)
    {
        return static::elasticsearch()->mappings($options, $callable);
    }

    public static function settings($settings = [], callable $callable = null)
    {
        return static::elasticsearch()->settings($settings, $callable);
    }

    public static function indexName()
    {
        $result = static::getOrSet('indexName', func_get_args());

        return $result;
    }

    public static function documentType()
    {
        return static::getOrSet('documentType', func_get_args());
    }

    public static function getDocument($primaryKey, $options = [])
    {
        $options = static::instanceOptions($primaryKey, $options);

        return static::elasticsearch()->client()->get($options);
    }

    public function indexDocument($options = [])
    {
        $options = static::instanceOptions($this->id, $options);
        $options['body'] = $this->toIndexedArray();
        return static::elasticsearch()->client()->index($options);
    }

    public function deleteDocument($options = [])
    {
        $options = static::instanceOptions($this->id, $options);

        return static::elasticsearch()->client()->delete($options);
    }

    public function updateDocument($options = [])
    {
        $dirty = $this->getDirty();

        if (empty($dirty)) {
            return $this->indexDocument($options);
        }

        $doc = array_only($this->toIndexedArray(), array_keys($dirty));
        $options = static::instanceOptions($this->id);
        $options['body'] = compact('doc');

        return static::elasticsearch()->client()->update($options);
    }

    public function updateDocumentAttributes($doc, $options = [])
    {
        $options = array_merge($options, static::instanceOptions($this->id));
        $options['body'] = compact('doc');

        return static::elasticsearch()->client()->update($options);
    }

    protected static function getOrSet($name, $args)
    {
        if (count($args) == 0) {
            return static::elasticsearch()->$name();
        }

        return static::elasticsearch()->$name($args[0]);
    }

    public static function instanceOptions($id, $options = [])
    {
        $options = array_merge([
            'index' => static::indexName(),
            'type' => static::documentType(),
        ], $options);

        if (!empty($id)) {
            $options['id'] = $id;
        }

        return $options;
    }
}
