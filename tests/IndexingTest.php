<?php namespace Datashaman\ElasticModel\Tests;

use Datashaman\ElasticModel\Traits\Mappings;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Log;

class IndexingTest extends TestCase
{
    public function testBootIndexing()
    {
        $changedAttributes = [
            'title' => 'Changed the title',
        ];

        $thing = Models\Thing::first();
        $thing->update($changedAttributes);
        $this->assertEquals($changedAttributes, $thing->_dirty);
    }

    public function testCreateIndex()
    {
        $mock = \Mockery::mock()
            ->shouldReceive([
                'create' => '',
                'exists' => false,
            ])
            ->mock();

        $expectations = [
            'indices' => $mock,
        ];

        $this->setClient($expectations);

        Models\Thing::settings([ 'number_of_shards' => 2 ]);
        Models\Thing::settings([ 'number_of_replicas' => 0 ]);

        Models\Thing::mappings([ 'foo' => 'boo' ]);

        Models\Thing::createIndex();
    }

    public function testCreateIndexThatExists()
    {
        $mock = \Mockery::mock()
            ->shouldReceive([
                'exists' => true
            ])
            ->mock();

        $expectations = [
            'indices' => $mock,
        ];

        $this->setClient($expectations);

        Models\Thing::createIndex();
    }

    public function testCreateIndexWithForce()
    {
        $mock = \Mockery::mock()
            ->shouldReceive([
                'exists' => false,
                'delete' => '',
                'create' => '',
            ])
            ->mock();

        $expectations = [
            'indices' => $mock,
        ];

        $this->setClient($expectations);

        Models\Thing::createIndex([ 'force' => true ]);
    }

    public function testCreateIndexWithForceThatExists()
    {
        $mock = \Mockery::mock()
            ->shouldReceive([
                'exists' => true,
                'delete' => '',
                'create' => '',
            ])
            ->mock();

        $expectations = [
            'indices' => $mock,
        ];

        $this->setClient($expectations);

        Models\Thing::createIndex([ 'force' => true ]);
    }

    public function testIndexExists()
    {
        $mock = \Mockery::mock()
            ->shouldReceive('exists')
            ->mock();

        $expectations = [
            'indices' => $mock,
        ];

        $this->setClient($expectations);

        Models\Thing::indexExists();
    }

    public function testDeleteIndex()
    {
        $mock = \Mockery::mock()
            ->shouldReceive('delete')
            ->mock();

        $expectations = [
            'indices' => $mock,
        ];

        $this->setClient($expectations);

        Models\Thing::deleteIndex();
    }

    public function testDeleteMissingIndex()
    {
        $mock = \Mockery::mock()
            ->shouldReceive('delete')
            ->andThrow(Missing404Exception::class, 'Index not found')
            ->mock();

        $expectations = [
            'indices' => $mock,
        ];

        $this->setClient($expectations);

        Models\Thing::deleteIndex();
    }

    public function testDeleteMissingIndexWithForce()
    {
        $mock = \Mockery::mock()
            ->shouldReceive('delete')
            ->andThrow(Missing404Exception::class, 'Index does not exist')
            ->mock();

        $expectations = [
            'indices' => $mock,
        ];

        $this->setClient($expectations);

        Log::shouldReceive('error', 'Index does not exist', [ 'index' => Models\Thing::indexName() ]);

        Models\Thing::deleteIndex([
            'force' => true,
        ]);
    }

    public function testIndexDocument()
    {
        $expectations = [
            'index' => [
                '_index' => Models\Thing::indexName(),
                '_type' => Models\Thing::documentType(),
                '_id' => 1,
                '_version' => 1,
                'created' => true,
            ],
        ];

        $client = $this->setClient($expectations);

        $thing = Models\Thing::first();
        $result = $thing->indexDocument();

        $this->assertEquals($expectations['index'], $result);
    }

    public function testGetDocument()
    {
        $thing = Models\Thing::first();

        $expectations = [
            'get' => [
                '_index' => Models\Thing::indexName(),
                '_type' => Models\Thing::documentType(),
                '_id' => 1,
                '_version' => 1,
                'found' => true,
                '_source' => $thing->toIndexedArray(),
            ],
        ];

        $this->setClient($expectations);

        $result = Models\Thing::getDocument($thing->id);
        $this->assertEquals($expectations['get'], $result);
    }

    public function testUpdateDocument()
    {
        $expectations = [
            'update' => [
                '_index' => Models\Thing::indexName(),
                '_type' => Models\Thing::documentType(),
                '_id' => 1,
                '_version' => 2,
            ],
        ];

        $this->setClient($expectations);

        $thing = Models\Thing::first();
        $thing->update([
            'title' => 'Changed the title',
        ]);

        $result = $thing->updateDocument();
        $this->assertEquals($expectations['update'], $result);
    }

    public function testUpdateUnchangedDocument()
    {
        $expectations = [
            'index' => [
                '_index' => Models\Thing::indexName(),
                '_type' => Models\Thing::documentType(),
                '_id' => 1,
                '_version' => 2,
            ],
        ];

        $this->setClient($expectations);

        $thing = Models\Thing::first();
        $thing->update([]);

        $result = $thing->updateDocument();
        $this->assertEquals($expectations['index'], $result);
    }

    public function testDeleteDocument()
    {
        $thing = Models\Thing::first();

        $expectations = [
            'delete' => [
                '_index' => Models\Thing::indexName(),
                '_type' => Models\Thing::documentType(),
                '_id' => 1,
                '_version' => 2,
                'found' => 1,
            ],
        ];

        $this->setClient($expectations);

        $result = $thing->deleteDocument();
        $this->assertEquals($expectations['delete'], $result);
    }

    public function testMappingsClass()
    {
        $mappings = Models\Thing::mappings();
        $this->assertInstanceOf(Mappings::class, $mappings);
    }

    public function testMappingsDefineProperties()
    {
        $mappings = new Mappings('thing');

        $mappings->indexes('foo', [
            'type' => 'boolean',
            'include_in_all' => false,
        ]);

        $this->assertEquals('boolean', array_get($mappings->toArray(), 'thing.properties.foo.type'));
    }

    public function testMappingsDefineTypeAsStringByDefault()
    {
        $mappings = new Mappings('thing');
        $mappings->indexes('bar', []);

        $this->assertEquals('string', array_get($mappings->toArray(), 'thing.properties.bar.type'));
    }

    public function testMappingsDefineMultipleFields()
    {
        $mappings = new Mappings('thing');

        $mappings->indexes('foo_1', [ 'type' => 'string' ], function ($m, $parent) {
            $m->indexes("$parent.raw", [ 'analyzer' => 'keyword' ]);
        });

        $mappings->indexes('foo_2', [ 'type' => 'multi_field' ], function ($m, $parent) {
            $m->indexes("$parent.raw", [ 'analyzer' => 'keyword' ]);
        });

        $array = $mappings->toArray();

        $this->assertEquals('string', array_get($array, 'thing.properties.foo_1.type'));
        $this->assertEquals('string', array_get($array, 'thing.properties.foo_1.fields.raw.type'));
        $this->assertEquals('keyword', array_get($array, 'thing.properties.foo_1.fields.raw.analyzer'));
        $this->assertNull(array_get($array, 'thing.properties.foo_1.properties'));

        $this->assertEquals('multi_field', array_get($array, 'thing.properties.foo_2.type'));
        $this->assertEquals('string', array_get($array, 'thing.properties.foo_2.fields.raw.type'));
        $this->assertEquals('keyword', array_get($array, 'thing.properties.foo_2.fields.raw.analyzer'));
        $this->assertNull(array_get($array, 'thing.properties.foo_2.properties'));
    }

    public function testMappingsDefineEmbeddedProperties()
    {
        $mappings = new Mappings('thing');

        $mappings->indexes('foo', [], function ($m, $parent) {
            $m->indexes("$parent.bar");
        });

        $mappings->indexes('foo_object', ['type' => 'object'], function ($m, $parent) {
            $m->indexes("$parent.bar");
        });

        $mappings->indexes('foo_nested', ['type' => 'nested'], function ($m, $parent) {
            $m->indexes("$parent.bar");
        });

        $array = $mappings->toArray();

        $this->assertEquals('object', array_get($array, 'thing.properties.foo.type'));
        $this->assertEquals('string', array_get($array, 'thing.properties.foo.properties.bar.type'));
        $this->assertNull(array_get($array, 'thing.properties.foo.fields'));

        $this->assertEquals('object', array_get($array, 'thing.properties.foo_object.type'));
        $this->assertEquals('string', array_get($array, 'thing.properties.foo_object.properties.bar.type'));
        $this->assertNull(array_get($array, 'thing.properties.foo_object.fields'));

        $this->assertEquals('nested', array_get($array, 'thing.properties.foo_nested.type'));
        $this->assertEquals('string', array_get($array, 'thing.properties.foo_nested.properties.bar.type'));
        $this->assertNull(array_get($array, 'thing.properties.foo_object.fields'));
    }

    public function testMappingsToArray()
    {
        $mappings = new Mappings('thing');

        $this->assertEquals([], $mappings->toArray());

        $mappings->indexes('foo', [], function ($m, $parent) {
            $m->indexes("$parent.bar");
        });

        $this->assertEquals([
            "thing" => [
                "properties" => [
                    "foo" => [
                        "type" => "object",
                        "properties" => [
                            "bar" => [
                                "type" => "string",
                            ],
                        ],
                    ],
                ],
            ],
        ], $mappings->toArray());
    }

    public function testMappingsUpdateAndReturn()
    {
        Models\Thing::mapping([ 'foo' => 'boo' ]);
        Models\Thing::mapping([ 'bar' => 'bam' ]);

        $this->assertEquals([
            'thing' => [
                'foo' => 'boo',
                'bar' => 'bam',
                'properties' => [],
            ],
        ], Models\Thing::mappings()->toArray());
    }

    public function testMappingsClosure()
    {
        Models\Thing::mapping([], function ($m) {
            $m->indexes('foo');
        });

        $this->assertEquals([
            'thing' => [
                'properties' => [
                    'foo' => [
                        'type' => 'string',
                    ],
                ],
            ],
        ], Models\Thing::mapping()->toArray());
    }
}
