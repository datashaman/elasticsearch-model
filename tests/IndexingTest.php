<?php namespace Datashaman\ElasticModel\Tests;

use Elasticsearch\Common\Exceptions\Missing404Exception;
use Mockery;

class IndexingTest extends TestCase
{
    public function testBootIndexing()
    {
        $thing = Models\Thing::first();
        $thing->title = 'Changed the title';
        $thing->save();

        $this->assertEquals([ 'title' => 'Changed the title' ], $thing->_dirty);
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

        $client = Mockery::mock('Elasticsearch\Client')
            ->shouldReceive($expectations)
            ->mock();

        Models\Thing::client($client);

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

        $client = Mockery::mock('Elasticsearch\Client')
            ->shouldReceive($expectations)
            ->mock();

        Models\Thing::client($client);

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

        $client = Mockery::mock('Elasticsearch\Client')
            ->shouldReceive($expectations)
            ->mock();

        Models\Thing::client($client);

        $thing = Models\Thing::first();
        $thing->title = 'Changed the title';
        $thing->save();

        $result = $thing->updateDocument();
        $this->assertEquals($expectations['update'], $result);
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

        $client = Mockery::mock('Elasticsearch\Client')
            ->shouldReceive($expectations)
            ->mock();

        Models\Thing::client($client);

        $result = $thing->deleteDocument();
        $this->assertEquals($expectations['delete'], $result);
    }
}
