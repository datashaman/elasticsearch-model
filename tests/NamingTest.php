<?php namespace Datashaman\ElasticModel\Tests;

use Elasticsearch\Client;
use stdClass;

class NamingTest extends TestCase
{
    public function testGetDocumentType()
    {
        $documentType = Models\Thing::documentType();
        $this->assertEquals('thing', $documentType);
    }

    public function testSetDocumentType()
    {
        Models\Thing::documentType('thingybob');
        $this->assertEquals('thingybob', Models\Thing::documentType());
    }

    public function testGetIndexName()
    {
        $indexName = Models\Thing::indexName();
        $this->assertNotNull(Models\Thing::indexName());
    }

    public function testSetIndexName()
    {
        Models\Thing::indexName('thingybobs');
        $this->assertEquals('thingybobs', Models\Thing::indexName());
    }
}
