<?php

namespace Datashaman\Elasticsearch\Model\Tests;

use Datashaman\Elasticsearch\Model\ElasticsearchModel;
use Elasticsearch\Client;

class ProxyTestModel
{
    use ElasticsearchModel;
    protected static $elasticsearch;
}

class ProxyTestModelWithProperties
{
    use ElasticsearchModel;
    protected static $elasticsearch;

    public static $indexName = 'foo';
    public static $documentType = 'bar';
}

class ProxyTest extends TestCase
{
    public function testGetClient()
    {
        $this->assertInstanceOf(Client::class, ProxyTestModel::elasticsearch()->client());
    }

    public function testSetClient()
    {
        ProxyTestModel::elasticsearch()->client('foobar');
        $this->assertSame('foobar', ProxyTestModel::elasticsearch()->client());
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
