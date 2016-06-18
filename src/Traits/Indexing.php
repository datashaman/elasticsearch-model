<?php namespace Datashaman\ElasticModel\Traits;

use Elasticsearch\Common\Exceptions\Missing404Exception;
use Closure;
use Log;

class Mappings
{
    protected $type;
    protected $options;
    protected $mapping;

    protected static $typesWithEmbeddedProperties = [
        'object',
        'nested',
    ];

    public function __construct($type, $options=[])
    {
        $this->type = $type;
        $this->options = $options;
        $this->mapping = [];
    }

    public function indexes($name, $options=[], Closure $closure=null)
    {
        array_set($this->mapping, $name, $options);

        if (!is_null($closure)) {
            $this->mapping = array_add($this->mapping, "$name.type", 'object');

            $type = array_get($this->mapping, "$name.type");
            $properties = in_array($type, static::$typesWithEmbeddedProperties) ? 'properties' : 'fields';

            $this->mapping = array_add($this->mapping, "$name.$properties", []);

            call_user_func($closure, $this, "$name.$properties");
        }

        $this->mapping = array_add($this->mapping, "$name.type", 'string');

        return $this;
    }

    public function toArray()
    {
        if (empty($this->options) && empty($this->mapping)) {
            return [];
        } else {
            $properties = $this->mapping;
            $type = array_merge($this->options, compact('properties'));
            return [ $this->type => $type ];
        }
    }

    public function mergeOptions($options)
    {
        $this->options = array_merge($this->options, $options);
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
                Log::error($e->getMessage(), compact('index'));
            } else {
                throw $e;
            }
        }
    }

    public static function mapping($options=[], Closure $closure=null)
    {
        if (empty(static::$mapping)) {
            static::$mapping = new Mappings(static::documentType());
        }

        if (!empty($options)) {
            static::$mapping->mergeOptions($options);
        }

        if (is_null($closure)) {
            return static::$mapping;
        } else {
            call_user_func($closure, static::$mapping);
            return static::class;
        }

    }

    public static function mappings($options=[])
    {
        return static::mapping($options);
    }

    public static function settings($settings=[])
    {
        if (empty(static::$settings)) {
            static::$settings = new Settings($settings);
        } else {
            if (!empty($settings)) {
                static::$settings->merge($settings);
            }
        }

        return static::$settings;
    }

    public static function createIndex($options=[])
    {
        $index = array_get($options, 'index', static::indexName());

        if (array_get($options, 'force')) {
            $options['index'] = $index;
            static::deleteIndex($options);
        }

        if (static::indexExists(compact('index'))) {
            return false;
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
