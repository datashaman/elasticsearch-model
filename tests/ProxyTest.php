<?php namespace Datashaman\ElasticModel\Tests;

use Elasticsearch\Client;

class ProxyTest extends TestCase
{
    public function testGetClient()
    {
        $this->assertInstanceOf(Client::class, Models\Thing::elastic()->client());
    }

    public function testSetClient()
    {
        Models\Thing::elastic()->client('foobar');
        $this->assertSame('foobar', Models\Thing::elastic()->client());
    }

    public function testGetDocumentType()
    {
        $this->assertEquals('thing', Models\Thing::elastic()->documentType());
    }

    public function testSetDocumentType()
    {
        Models\Thing::elastic()->documentType('thingybob');
        $this->assertEquals('thingybob', Models\Thing::elastic()->documentType());
    }

    public function testGetIndexName()
    {
        $this->assertNotNull('things', Models\Thing::elastic()->indexName());
    }

    public function testSetIndexName()
    {
        Models\Thing::elastic()->indexName('thingybobs');
        $this->assertEquals('thingybobs', Models\Thing::elastic()->indexName());
    }
}
