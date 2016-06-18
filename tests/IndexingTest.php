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

    public function testGetDocumentSource()
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

        $result = Models\Thing::getDocumentSource($thing->id);
        $this->assertEquals($expectations['get']['_source'], $result);
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
}
