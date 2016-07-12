<?php

namespace Datashaman\Elasticsearch\Model\Tests;

use Datashaman\Elasticsearch\Model\Elasticsearch;
use Datashaman\Elasticsearch\Model\ElasticsearchModel;
use Datashaman\Elasticsearch\Model\Mappings;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Log;
use Mockery as m;
use Schema;
use stdClass;
use Storage;

class IndexingTestModel extends Model
{
    use ElasticsearchModel;
    protected static $elasticsearch;
    public $id = 1;
}

class EloquentModel extends Model
{
    use ElasticsearchModel;
    protected static $elasticsearch;
}

class EloquentModelTwo extends Model
{
    use ElasticsearchModel;
    protected static $elasticsearch;

    public function toIndexedArray()
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
        ];
    }
}

class IndexingTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->createThings();

        IndexingTestModel::resetElasticsearch();
        EloquentModel::resetElasticsearch();
        EloquentModelTwo::resetElasticsearch();

        Schema::create('indexing_test_models', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('eloquent_models', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('eloquent_model_twos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->text('description');
            $table->timestamps();
        });
    }

    public function testInitializeIndexSettings()
    {
        IndexingTestModel::settings(['foo' => 'bar']);
        IndexingTestModel::settings(['bar' => 'bam']);

        $this->assertEquals(['foo' => 'bar', 'bar' => 'bam'], IndexingTestModel::settings()->toArray());
    }

    public function testInitializeIndexSettingsFromYmlFile()
    {
        Storage::shouldReceive('get', 'model.yml')
            ->andReturn(file_get_contents(__DIR__.'/fixtures/model.yml'));

        IndexingTestModel::settings('model.yml');
        IndexingTestModel::settings(['bar' => 'bam']);

        $this->assertEquals(['bar' => 'bam', 'baz' => 'qux'], IndexingTestModel::settings()->toArray());
    }

    public function testInitializeIndexSettingsFromYamlFile()
    {
        Storage::shouldReceive('get', 'model.yaml')
            ->andReturn(file_get_contents(__DIR__.'/fixtures/model.yaml'));

        IndexingTestModel::settings('model.yaml');
        IndexingTestModel::settings(['bar' => 'bam']);

        $this->assertEquals(['bar' => 'bam', 'baz' => 'qux'], IndexingTestModel::settings()->toArray());
    }

    public function testInitializeIndexSettingsFromJsonFile()
    {
        Storage::shouldReceive('get', 'model.json')
            ->andReturn(file_get_contents(__DIR__.'/fixtures/model.json'));

        IndexingTestModel::settings('model.json');
        IndexingTestModel::settings(['bat' => 'bap']);

        $this->assertEquals(['bat' => 'bap', 'baz' => 'qux'], IndexingTestModel::settings()->toArray());
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

        $mappings->indexes('foo_1', ['type' => 'string'], function ($m, $parent) {
            $m->indexes("$parent.raw", ['analyzer' => 'keyword']);
        });

        $mappings->indexes('foo_2', ['type' => 'multi_field'], function ($m, $parent) {
            $m->indexes("$parent.raw", ['analyzer' => 'keyword']);
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
            'thing' => [
                'properties' => [
                    'foo' => [
                        'type' => 'object',
                        'properties' => [
                            'bar' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                ],
            ],
        ], $mappings->toArray());
    }

    public function testMappingsUpdateAndReturn()
    {
        Models\Thing::elasticsearch()->mapping(['foo' => 'boo']);
        Models\Thing::elasticsearch()->mapping(['bar' => 'bam']);

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

    public function testCreateIndex()
    {
        $create = new stdClass;

        $client = m::mock('Client');

        $client->shouldReceive('indices->create')
            ->with([
                'index' => 'indexing-test-models',
                'body' => [
                    'settings' => ['foo' => 'bar'],
                    'mappings' => [
                        'indexing-test-model' => [
                            'bom' => 'dia',
                            'properties' => [],
                        ],
                    ],
                ],
            ])
            ->andReturn($create);

        $elastic = m::mock(Elasticsearch::class, [IndexingTestModel::class], [
            'client' => $client,
            'indexExists' => false,
            'indexName' => 'indexing-test-models',
            'documentType' => 'indexing-test-model',
        ])->shouldDeferMissing();

        IndexingTestModel::elasticsearch($elastic);
        IndexingTestModel::settings(['foo' => 'bar']);
        IndexingTestModel::mappings(['bom' => 'dia']);

        $this->assertSame($create, IndexingTestModel::elasticsearch()->createIndex());
    }

    public function testCreateIndexThatExists()
    {
        $elastic = m::mock(Elasticsearch::class, [IndexingTestModel::class], [
            'indexExists' => true,
            'indexName' => 'indexing-test-models',
            'documentType' => 'indexing-test-model',
        ])->shouldDeferMissing();

        $this->assertFalse($elastic->createIndex());
    }

    public function testCreateIndexWithForce()
    {
        $create = new stdClass;

        $client = m::mock('Client');

        $client->shouldReceive('indices->create')
            ->with([
                'index' => 'indexing-test-models',
                'body' => [],
            ])
            ->andReturn($create);

        $elastic = m::mock(Elasticsearch::class, [IndexingTestModel::class], [
            'client' => $client,
            'indexName' => 'indexing-test-models',
            'documentType' => 'indexing-test-model',
        ])->shouldDeferMissing();

        $elastic->shouldReceive('deleteIndex')
            ->with(['force' => true, 'index' => 'indexing-test-models']);

        $elastic->shouldReceive('indexExists')
            ->with(['index' => 'indexing-test-models']);

        $this->assertSame($create, $elastic->createIndex(['force' => true]));
    }

    public function testCreateIndexWithForceThatExists()
    {
        $elastic = m::mock(Elasticsearch::class, [IndexingTestModel::class], [
            'indexName' => 'indexing-test-models',
            'documentType' => 'indexing-test-model',
        ])->shouldDeferMissing();

        $elastic->shouldReceive('deleteIndex')
            ->with(['force' => true, 'index' => 'indexing-test-models']);

        $elastic->shouldReceive('indexExists')
            ->with(['index' => 'indexing-test-models'])
            ->andReturn(true);

        $this->assertFalse($elastic->createIndex(['force' => true]));
    }

    public function testIndexExists()
    {
        $client = m::mock('Client');
        $client->shouldReceive('indices->exists')
            ->with([
                'index' => 'indexing-test-models',
            ])
            ->andReturn(true);

        $elastic = m::mock(Elasticsearch::class, [IndexingTestModel::class], [
            'client' => $client,
            'indexName' => 'indexing-test-models',
            'documentType' => 'indexing-test-model',
        ])->shouldDeferMissing();

        $this->assertTrue($elastic->indexExists());
    }

    public function testDeleteIndex()
    {
        $delete = new stdClass;

        $client = m::mock('Client');
        $client->shouldReceive('indices->delete')
            ->with([
                'index' => 'indexing-test-models',
            ])
            ->andReturn($delete);

        $elastic = m::mock(Elasticsearch::class, [IndexingTestModel::class], [
            'client' => $client,
            'indexName' => 'indexing-test-models',
            'documentType' => 'indexing-test-model',
        ])->shouldDeferMissing();

        $this->assertSame($delete, $elastic->deleteIndex());
    }

    public function testDeleteMissingIndex()
    {
        $client = m::mock('Client');

        $client->shouldReceive('indices->delete')
            ->andThrow(Missing404Exception::class, 'Index is missing');

        $elastic = m::mock(Elasticsearch::class, [IndexingTestModel::class], [
            'client' => $client,
            'indexName' => 'indexing-test-models',
        ])->shouldDeferMissing();

        $this->setExpectedException(Missing404Exception::class, 'Index is missing');
        $elastic->deleteIndex();
    }

    public function testDeleteMissingIndexWithForce()
    {
        $client = m::mock('Client');

        $client->shouldReceive('indices->delete')
            ->andThrow(Missing404Exception::class, 'Index is missing');

        $elastic = m::mock(Elasticsearch::class, [IndexingTestModel::class], [
            'client' => $client,
            'indexName' => 'indexing-test-models',
        ])->shouldDeferMissing();

        Log::shouldReceive('error')
            ->with('Index is missing', ['index' => 'indexing-test-models']);

        $this->assertFalse($elastic->deleteIndex(['force' => true]));
    }

    public function testIndexDocument()
    {
        $client = m::mock('Client');

        $client->shouldReceive('index')
            ->with([
                'index' => 'indexing-test-models',
                'type' => 'indexing-test-model',
                'id' => 1,
                'body' => [
                    'id' => 1,
                    'foo' => 'bar',
                ],
            ]);

        $elastic = m::mock(Elasticsearch::class, [IndexingTestModel::class], [
            'client' => $client,
            'indexName' => 'indexing-test-models',
            'documentType' => 'indexing-test-model',
        ])->shouldDeferMissing();

        IndexingTestModel::elasticsearch($elastic);

        $instance = m::mock(IndexingTestModel::class, [
            'toArray' => [
                'id' => 1,
                'foo' => 'bar',
            ],
            'first' => '',
        ])->shouldDeferMissing();

        $instance->indexDocument();
    }

    public function testGetDocument()
    {
        $expected = [
            '_index' => 'indexing-test-models',
            '_type' => 'indexing-test-model',
            '_id' => 1,
            '_version' => 1,
            'found' => true,
            '_source' => [
                'id' => 1,
                'foo' => 'bar',
            ],
        ];

        $client = m::mock('Client');

        $client->shouldReceive('get')
            ->with([
                'index' => 'indexing-test-models',
                'type' => 'indexing-test-model',
                'id' => 1,
            ])
            ->andReturn([
                '_index' => 'indexing-test-models',
                '_type' => 'indexing-test-model',
                '_id' => 1,
                '_version' => 1,
                'found' => true,
                '_source' => [
                    'id' => 1,
                    'foo' => 'bar',
                ],
            ]);

        $elastic = m::mock(Elasticsearch::class, [IndexingTestModel::class], [
            'client' => $client,
            'indexName' => 'indexing-test-models',
            'documentType' => 'indexing-test-model',
        ])->shouldDeferMissing();

        IndexingTestModel::elasticsearch($elastic);

        $this->assertEquals($expected, IndexingTestModel::getDocument(1));
    }

    public function testUpdateDocument()
    {
        Models\Thing::updated(function ($instance) {
            $instance->updateDocument();
        });

        $client = m::mock('Client');

        $client->shouldReceive('update')
            ->with([
                'index' => 'indexing-test-models',
                'type' => 'indexing-test-model',
                'id' => 2,
                'body' => [
                    'doc' => [
                        'title' => 'other title',
                    ],
                ],
            ])
            ->andReturn([
                '_index' => 'indexing-test-models',
                '_type' => 'indexing-test-model',
                '_id' => 2,
                '_version' => 2,
            ]);

        $elastic = m::mock(Elasticsearch::class, [IndexingTestModel::class], [
            'client' => $client,
            'indexName' => 'indexing-test-models',
            'documentType' => 'indexing-test-model',
        ])->shouldDeferMissing();

        IndexingTestModel::elasticsearch($elastic);

        $thing = new IndexingTestModel;
        $thing->title = 'Title';
        $thing->save();

        $thing->title = 'other title';
        $thing->save();
    }

    public function testUpdateUnchangedDocumentByReindexing()
    {
        $elastic = m::mock(Elasticsearch::class, [Models\Thing::class], [
            'indexDocument' => '',
            'indexName' => 'things',
            'documentType' => 'thing',
        ]);

        Models\Thing::elasticsearch($elastic);

        $instance = new Models\Thing;
        $instance->updateDocument();
    }

    public function testExcludeAttributesNotInIndexedArray()
    {
        EloquentModelTwo::updated(function ($instance) {
            $instance->updateDocument();
        });

        $client = m::mock('Client');

        $client->shouldReceive('update')
            ->with([
                'index' => 'eloquent-model-twos',
                'type' => 'eloquent-model-two',
                'id' => 1,
                'body' => [
                    'doc' => [
                        'title' => 'A new title',
                    ],
                ],
            ])
            ->andReturn([
                '_index' => 'eloquent-model-twos',
                '_type' => 'eloquent-model-two',
                '_id' => 1,
                '_version' => 2,
            ]);

        $elastic = m::mock(Elasticsearch::class, [EloquentModelTwo::class], [
            'client' => $client,
            'indexName' => 'eloquent-model-twos',
            'documentType' => 'eloquent-model-two',
        ])->shouldDeferMissing();

        EloquentModelTwo::elasticsearch($elastic);

        $instance = new EloquentModelTwo;
        $instance->title = 'A title';
        $instance->description = 'A description';
        $instance->save();

        $instance->title = 'A new title';
        $instance->description = 'A new description';
        $instance->save();
    }

    public function testUpdateSpecificAttributes()
    {
        $client = m::mock('Client');

        $client->shouldReceive('update')
            ->with([
                'index' => 'eloquent-model-twos',
                'type' => 'eloquent-model-two',
                'id' => 1,
                'body' => [
                    'doc' => [
                        'title' => 'A green title',
                    ],
                ],
                'refresh' => true,
            ])
            ->andReturn([
                '_index' => 'eloquent-model-twos',
                '_type' => 'eloquent-model-two',
                '_id' => 1,
                '_version' => 2,
            ]);

        $elastic = m::mock(Elasticsearch::class, [EloquentModelTwo::class], [
            'client' => $client,
            'indexName' => 'eloquent-model-twos',
            'documentType' => 'eloquent-model-two',
        ])->shouldDeferMissing();

        EloquentModelTwo::elasticsearch($elastic);

        $instance = new EloquentModelTwo;
        $instance->title = 'A title';
        $instance->description = 'A description';
        $instance->save();

        $instance->updateDocumentAttributes(['title' => 'A green title'], ['refresh' => true]);

        $this->assertEquals('A title', $instance->title);
    }

    public function testPassOptionsToUpdateSpecificAttributes()
    {
        $client = m::mock('Client');

        $client->shouldReceive('update')
            ->with([
                'index' => 'eloquent-model-twos',
                'type' => 'eloquent-model-two',
                'id' => 1,
                'body' => [
                    'doc' => [
                        'title' => 'A green title',
                    ],
                ],
            ])
            ->andReturn([
                '_index' => 'eloquent-model-twos',
                '_type' => 'eloquent-model-two',
                '_id' => 1,
                '_version' => 2,
            ]);

        $elastic = m::mock(Elasticsearch::class, [EloquentModelTwo::class], [
            'client' => $client,
            'indexName' => 'eloquent-model-twos',
            'documentType' => 'eloquent-model-two',
        ])->shouldDeferMissing();

        EloquentModelTwo::elasticsearch($elastic);

        $instance = new EloquentModelTwo;
        $instance->title = 'A title';
        $instance->description = 'A description';
        $instance->save();

        $instance->updateDocumentAttributes(['title' => 'A green title']);

        $this->assertEquals('A title', $instance->title);
    }

    public function testDeleteDocument()
    {
        $thing = Models\Thing::first();

        $client = m::mock('Client')
            ->shouldReceive('delete')
            ->with([
                'index' => 'things',
                'type' => 'thing',
                'id' => 1,
            ])
            ->mock();

        Models\Thing::elasticsearch()->client($client);

        $thing->deleteDocument();
    }
}
