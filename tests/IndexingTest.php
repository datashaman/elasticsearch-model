<?php namespace Datashaman\ElasticModel\Tests;

use Elasticsearch\Common\Exceptions\Missing404Exception;

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
        $thing = Models\Thing::first();
        $result = $thing->indexDocument();

        $this->assertEquals(Models\Thing::indexName(), $result['_index']);
        $this->assertEquals(Models\Thing::documentType(), $result['_type']);
        $this->assertEquals($thing->id, $result['_id']);
        $this->assertEquals(1, $result['_version']);
        $this->assertEquals(true, $result['created']);
    }

    public function testGetDocument()
    {
        $thing = Models\Thing::first();
        $thing->indexDocument();

        $result = Models\Thing::getDocument($thing->id);

        $this->assertEquals(Models\Thing::indexName(), $result['_index']);
        $this->assertEquals(Models\Thing::documentType(), $result['_type']);
        $this->assertEquals($thing->id, $result['_id']);
        $this->assertEquals(1, $result['_version']);
        $this->assertEquals(true, $result['found']);
        $this->assertEquals($thing->toIndexedArray(), $result['_source']);
    }

    public function testUpdateDocument()
    {
        $thing = Models\Thing::first();
        $thing->indexDocument();

        $thing->title = 'Changed the title';
        $thing->save();

        $result = $thing->updateDocument();

        $this->assertEquals(Models\Thing::indexName(), $result['_index']);
        $this->assertEquals(Models\Thing::documentType(), $result['_type']);
        $this->assertEquals($thing->id, $result['_id']);
        $this->assertEquals(2, $result['_version']);

        $result = Models\Thing::getDocument($thing->id);

        $this->assertEquals(2, $result['_version']);
        $this->assertEquals('Changed the title', $result['_source']['title']);
    }

    public function testDeleteDocument()
    {
        $thing = Models\Thing::first();
        $thing->indexDocument();

        $result = $thing->deleteDocument();

        $this->expectException(Missing404Exception::class);
        Models\Thing::getDocument($thing->id);
    }
}
