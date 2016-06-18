<?php namespace Datashaman\ElasticModel\Tests;

use Elasticsearch\Client;
use stdClass;

class ClientTest extends TestCase
{
    public function testGetClient()
    {
        $client = Models\Thing::client();
        $this->assertInstanceOf(Client::class, $client);
    }

    public function testSetClient()
    {
        $client = new stdClass;
        Models\Thing::client($client);
        $this->assertSame($client, Models\Thing::client());
    }
}
