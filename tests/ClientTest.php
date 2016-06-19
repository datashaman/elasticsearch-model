<?php namespace Datashaman\ElasticModel\Tests;

use Elasticsearch\Client;

class ClientTest extends TestCase
{
    public function testDefaultClientMethod()
    {
        $instance = new Models\Thing;

        $this->assertInstanceOf(Client::class, Models\Thing::client());
        $this->assertInstanceOf(Client::class, $instance->client());
    }

    public function testSetClientForModel()
    {
        $instance = new Models\Thing;
        Models\Thing::client('foobar');
        $this->assertSame('foobar', Models\Thing::client());
        $this->assertSame('foobar', $instance->client());
    }

    public function testSetClientForModelInstance()
    {
        $instance = new Models\Thing;
        $instance->client('moobam');
        $this->assertSame('moobam', $instance->client());
    }
}
