<?php namespace Datashaman\ElasticModel\Tests;

use AspectMock\Test as test;
use Datashaman\ElasticModel\Mappings;
use Elasticsearch\clientBuilder;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Elasticsearch\Namespaces\IndicesNamespace;
use Log;
use stdClass;

class IndexingTest extends TestCase
{
    public function testCreateIndex()
    {
        $create = new stdClass;
        $indices = test::double(IndicesNamespace::class, compact('create'));

        test::double(Models\Thing::elastic(), [
            'indexExists' => false,
        ]);

        $this->assertSame($create, Models\Thing::elastic()->createIndex());

        $indices->verifyInvoked('create', [[ 'index' => 'things', 'body' => [] ]]);
    }

    public function testCreateIndexThatExists()
    {
        $create = new stdClass;
        $indices = test::double(IndicesNamespace::class, compact('create'));

        test::double(Models\Thing::elastic(), [
            'indexExists' => true,
        ]);

        $this->assertFalse(Models\Thing::elastic()->createIndex());

        $indices->verifyNeverInvoked('create');
    }

    public function testCreateIndexWithForce()
    {
        $create = new stdClass;
        $indices = test::double(IndicesNamespace::class, compact('create'));

        $thing = test::double(Models\Thing::elastic(), [
            'deleteIndex' => null,
            'indexExists' => false,
        ]);

        $this->assertSame($create, Models\Thing::elastic()->createIndex([ 'force' => true ]));

        $thing->verifyInvoked('deleteIndex', [[ 'force' => true, 'index' => 'things' ]]);
        $thing->verifyInvoked('indexExists', [[ 'index' => 'things' ]]);

        $indices->verifyInvoked('create', [[ 'index' => 'things', 'body' => [] ]]);
    }

    public function testCreateIndexWithForceThatExists()
    {
        $indices = test::double(IndicesNamespace::class, [
            'create' => null,
        ]);

        $thing = test::double(Models\Thing::elastic(), [
            'deleteIndex' => null,
            'indexExists' => true,
        ]);

        $this->assertFalse(Models\Thing::elastic()->createIndex([ 'force' => true ]));

        $thing->verifyInvoked('deleteIndex', [[ 'force' => true, 'index' => 'things' ]]);
        $thing->verifyInvoked('indexExists', [[ 'index' => 'things' ]]);

        $indices->verifyNeverInvoked('create');
    }

    public function testIndexExists()
    {
        $indices = test::double(IndicesNamespace::class, [
            'exists' => true,
        ]);

        $this->assertTrue(Models\Thing::elastic()->indexExists());

        $indices->verifyInvoked('exists', [[ 'index' => 'things' ]]);
    }

    public function testDeleteIndex()
    {
        $delete = new stdClass;
        $indices = test::double(IndicesNamespace::class, compact('delete'));

        $this->assertSame($delete, Models\Thing::elastic()->deleteIndex());

        $indices->verifyInvoked('delete', [[ 'index' => 'things' ]]);
    }

    public function testDeleteMissingIndex()
    {
        $delete = function () { throw new Missing404Exception('Index is missing'); };
        $indices = test::double(IndicesNamespace::class, compact('delete'));

        $this->expectException(Missing404Exception::class);
        $this->expectExceptionMessage('Index is missing');

        Models\Thing::elastic()->deleteIndex();
    }

    public function testDeleteMissingIndexWithForce()
    {
        $delete = function () { throw new Missing404Exception('Index is missing'); };
        $indices = test::double(IndicesNamespace::class, compact('delete'));
        $log = test::double(Log::class, [
            'error' => null,
        ]);

        $this->assertFalse(Models\Thing::elastic()->deleteIndex([ 'force' => true ]));

        // $log->verifyInvoked('error', [ 'Index is missing', [ 'index' => 'things' ]]);
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

        $this->assertEquals($expectations['index'], $thing->indexDocument());

        $client->verifyInvoked('index', [[
            'index' => 'things',
            'type' => 'thing',
            'id' => 1,
            'body' => $thing->toIndexedArray(),
        ]]);
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

        $client = $this->setClient($expectations);

        $this->assertEquals($expectations['get'], Models\Thing::getDocument($thing->id));

        $client->verifyInvoked('get', [[
            'index' => 'things',
            'type' => 'thing',
            'id' => 1,
        ]]);
    }

    public function testUpdateDocument()
    {
        Models\Thing::updated(function ($thing) {
            $thing->updateDocument();
        });

        $expectations = [
            'update' => [
                '_index' => Models\Thing::indexName(),
                '_type' => Models\Thing::documentType(),
                '_id' => 1,
                '_version' => 2,
            ],
        ];

        $client = $this->setClient($expectations);

        $thing = Models\Thing::first();
        $thing->title = 'Changed the title';
        $thing->save();

        $client->verifyInvoked('update', [[
            'index' => 'things',
            'type' => 'thing',
            'id' => 1,
            'body' => [
                'doc' => [
                    'title' => 'Changed the title',
                    'updated_at' => $thing->updated_at->toDateTimeString(),
                ],
            ],
        ]]);
    }

    public function testUpdateUnchangedDocument()
    {
        $indexDocument = new stdClass;
        $thingDouble = test::double(Models\Thing::class, compact('indexDocument'));

        $thing = Models\Thing::first();
        $thing->update([]);

        $this->assertEquals($indexDocument, $thing->updateDocument());

        $thingDouble->verifyInvoked('indexDocument', [[]]);
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
                'found' => true,
            ],
        ];

        $client = $this->setClient($expectations);

        $this->assertEquals($expectations['delete'], $thing->deleteDocument());

        $client->verifyInvoked('delete', [[
            'index' => 'things',
            'type' => 'thing',
            'id' => 1,
        ]]);
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
        Models\Thing::elastic()->mapping([ 'foo' => 'boo' ]);
        Models\Thing::elastic()->mapping([ 'bar' => 'bam' ]);

        $this->assertEquals([
            'thing' => [
                'foo' => 'boo',
                'bar' => 'bam',
                'properties' => [],
            ],
        ], Models\Thing::mappings()->toArray());
    }

    public function testMappingsCallable()
    {
        Models\Thing::mappings([], function ($m) {
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
        ], Models\Thing::mappings()->toArray());
    }
}
