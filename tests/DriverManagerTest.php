<?php

namespace Datashaman\Elasticsearch\Model\Tests;

use Datashaman\Elasticsearch\Model\Driver\EloquentDriver;
use Datashaman\Elasticsearch\Model\DriverManager;
use Datashaman\Elasticsearch\Model\Elasticsearch;
use Datashaman\Elasticsearch\Model\ElasticsearchModel;
use Datashaman\Elasticsearch\Model\Response;
use Datashaman\Elasticsearch\Model\SearchRequest;
use Mockery as m;

class DummyModel
{
    use ElasticsearchModel;
    protected static $elasticsearch;
}

class DummyDriverClass
{
}

class DummyDriver
{
}

class DriverManagerTest extends TestCase
{
    public function testReturnDriverInstance()
    {
        $elastic = m::mock(Elasticsearch::class, [IndexingTestModel::class], [
            'client' => m::mock('Client', [
                'search' => ['hits' => ['hits' => []]],
            ]),
            'indexName' => 'indexing-test-models',
            'documentType' => 'indexing-test-model',
        ])->shouldDeferMissing();

        DummyModel::elasticsearch($elastic);

        $search = new SearchRequest(DummyModel::class, '*');
        $response = new Response($search);
        $driverManager = new DriverManager($response);
        $this->assertInstanceOf(EloquentDriver::class, $driverManager->driver());
    }
}
