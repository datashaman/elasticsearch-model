<?php namespace Datashaman\ElasticModel\Traits;

use Elasticsearch\Common\Exceptions\Missing404Exception;

class Mappings
{
    protected $type;
    protected $options;
    protected $mapping;

    public function __construct($type, $options=[])
    {
        $this->type = $type;
        $this->options = $options;
        $this->mapping = [];
    }

    public function toArray()
    {
        $properties = $this->mapping;

        if (empty($properties)) {
            return [];
        } else {
            return [ $this->type => array_merge($this->options, compact('properties')) ];
        }
    }
}

class Settings
{
    protected $settings;

    public function __construct($settings=[])
    {
        $this->settings = $settings;
    }

    public function merge($settings)
    {
        $this->settings = array_merge($this->settings, $settings);
    }

    public function toArray()
    {
        return $this->settings;
    }
}


trait Indexing
{
    use Client;
    use Naming;
    use Serializing;

    protected static $settings;
    protected static $mapping;

    public $_dirty;

    public static function bootIndexing()
    {
        static::updating(function ($object) {
            $object->_dirty = $object->getDirty();
        });
    }

    public static function indexExists($options=[])
    {
        $index = array_get($options, 'index', static::indexName());
        return static::client()->indices()->exists(compact('index'));
    }

    public static function deleteIndex($options=[])
    {
        $index = array_get($options, 'index', static::indexName());
        try {
            return static::client()->indices()->delete(compact('index'));
        } catch (Missing404Exception $e) {
            if (array_get($options, 'force')) {
                Log::error('Index does not exist', compact('index'));
            }
        }
    }

    public static function mapping($options=[])
    {
        if (!isset(static::$mapping)) {
            static::$mapping = new Mappings(static::$documentType, $options);
        }

        if (!empty($options)) {
            static::$mapping = array_merge(static::$mapping, $options);
        }

        return static::$mapping;
    }

    public static function mappings($options=[])
    {
        return static::mapping($options);
    }

    public static function settings($settings=[])
    {
        if (isset(static::$settings)) {
            if (!empty($settings)) {
                static::$settings->merge($settings);
            }
        } else {
            static::$settings = new Settings($settings);
        }

        return static::$settings;
    }

    public static function createIndex($options=[])
    {
        $index = array_get($options, 'index', static::$indexName);

        if (array_get($options, 'force')) {
            $options['index'] = $index;
            static::deleteIndex($options);
        }

        if (static::indexExists(compact('index'))) {
            return False;
        } else {
            $body = [];

            $settings = static::settings()->toArray();
            if (!empty($settings)) {
                $body['settings'] = $settings;
            }

            $mappings = static::mappings()->toArray();
            if (!empty($mappings)) {
                $body['mappings'] = $mappings;
            }

            return static::client()->indices()->create(compact('index', 'body'));
        }
    }

    private static function _instanceArgs($id, $options)
    {
        $args = array_merge([
            'index' => static::indexName(),
            'type' => static::documentType(),
            'id' => $id,
        ], $options);
        return $args;
    }

    public static function getDocument($id, $options=[])
    {
        $args = static::_instanceArgs($id, $options);
        return static::client()->get($args);
    }

    public static function getDocumentSource($id, $options=[])
    {
        $response = static::getDocument($id, $options);

        if ($response['found']) {
            return $response['_source'];
        }
    }

    public function deleteDocument($options=[])
    {
        $args = static::_instanceArgs($this->id, $options);
        return static::client()->delete($args);
    }

    public function indexDocument($options=[])
    {
        $args = static::_instanceArgs($this->id, $options);
        $args['body'] = $this->toIndexedArray();
        return static::client()->index($args);
    }

    public function updateDocument($options=[])
    {
        if (empty($this->_dirty)) {
            return $this->indexDocument($options);
        } else {
            $doc = array_only($this->toIndexedArray(), array_keys($this->_dirty));
            return $this->updateDocumentAttributes($doc, $options);
        }
    }

    public function updateDocumentAttributes($doc, $options=[])
    {
        $args = static::_instanceArgs($this->id, $options);
        $args['body'] = array_merge(compact('doc'), $options);
        return static::client()->update($args);
    }
}
