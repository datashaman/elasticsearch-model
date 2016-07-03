<?php namespace Datashaman\ElasticModel;

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

        parent::__construct([
            'client' => ClientBuilder::create()->build(),
            'documentType' => isset($class::$documentType) ? $class::$documentType : str_slug($name),
            'indexName' => isset($class::$indexName) ? $class::$indexName : str_slug(str_plural($name)),
        ]);

        $this->class = $class;
    }
}

trait Proxy
{
    protected $_dirty;
    protected static $elasticsearch;

    public static function resetElasticModel()
    {
        static::$elasticsearch = null;
    }

    public static function elastic()
    {
        if (is_null(static::$elasticsearch)) {
            static::$elasticsearch = new Elasticsearch(static::class);
        }

        return static::$elasticsearch;
    }

    public static function search($query, $options=[])
    {
        return static::elastic()->search($query, $options);
    }

    public static function mappings($options=[], callable $callback=null)
    {
        return static::elastic()->mappings($options, $callback);
    }

    public static function settings($settings=[])
    {
        return static::elastic()->settings($settings);
    }

    public static function indexName()
    {
        return static::getOrSet('indexName', func_get_args());
    }

    public static function documentType()
    {
        return static::getOrSet('documentType', func_get_args());
    }

    public static function getDocument($primaryKey, $options=[])
    {
        $options = static::instanceOptions($primaryKey, $options);
        return static::elastic()->getDocument($options);
    }

    public function indexDocument($options=[])
    {
        $options = static::instanceOptions($this->id, $options);
        $options['body'] = $this->toIndexedArray();
        return static::elastic()->indexDocument($options);
    }

    public function deleteDocument($options=[])
    {
        $options = static::instanceOptions($this->id, $options);
        return static::elastic()->deleteDocument($options);
    }

    public function updateDocument($options=[])
    {
        $dirty = $this->getDirty();

        if (empty($dirty)) {
            return $this->indexDocument($options);
        }

        $doc = array_only($this->toIndexedArray(), array_keys($dirty));
        $options = static::instanceOptions($this->id);
        $options['body'] = compact('doc');
        return static::elastic()->updateDocument($options);
    }

    public function updateDocumentAttributes($doc, $options=[])
    {
        $options = static::instanceOptions($this->id);
        $options['body'] = array_merge(compact('doc'), $options);
        return static::elastic()->updateDocument($options);
    }

    private static function getOrSet($name, $args)
    {
        if (count($args) == 0) {
            return static::elastic()->$name();
        }

        return static::elastic()->$name($args[0]);
    }

    private static function instanceOptions($primaryKey, $options=[])
    {
        $options = array_merge([
            'index' => static::indexName(),
            'type' => static::documentType(),
            'id' => $primaryKey,
        ], $options);
        return $options;
    }
}
