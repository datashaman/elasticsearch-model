<?php namespace Datashaman\ElasticModel\Tests;

use Datashaman\ElasticModel\ElasticModel;
use Elasticsearch\Client;

class ProxyTestModel
{
    use ElasticModel;

    protected static $elasticsearch;
}

class ProxyTestModelWithProperties
{
    use ElasticModel;

    protected static $elasticsearch;

    public static $indexName = 'foo';
    public static $documentType = 'bar';
}

class ProxyTest extends TestCase
{
    public function testGetClient()
    {
        $this->assertInstanceOf(Client::class, ProxyTestModel::elastic()->client());
    }

    public function testSetClient()
    {
        ProxyTestModel::elastic()->client('foobar');
        $this->assertSame('foobar', ProxyTestModel::elastic()->client());
    }

    public function testGetDocumentType()
    {
        $this->assertEquals('proxy-test-model', ProxyTestModel::documentType());
    }

    public function testSetDocumentType()
    {
        ProxyTestModel::documentType('thingybob');
        $this->assertEquals('thingybob', ProxyTestModel::documentType());
    }

    public function testGetIndexName()
    {
        $this->assertEquals('proxy-test-models', ProxyTestModel::indexName());
    }

    public function testSetIndexName()
    {
        ProxyTestModel::indexName('thingybobs');
        $this->assertEquals('thingybobs', ProxyTestModel::indexName());
    }

    public function testGetIndexNameWithProperty()
    {
        $this->assertEquals('foo', ProxyTestModelWithProperties::indexName());
    }

    public function testGetDocumentTypeWithProperty()
    {
        $this->assertEquals('bar', ProxyTestModelWithProperties::documentType());
    }
}
