<?php namespace Datashaman\ElasticModel\Tests;

use Datashaman\ElasticModel\Driver\EloquentDriver;
use Datashaman\ElasticModel\DriverManager;
use Datashaman\ElasticModel\Elastic;
use Datashaman\ElasticModel\ElasticModel;
use Datashaman\ElasticModel\Response;
use Datashaman\ElasticModel\SearchRequest;
use Mockery as m;

class DummyModel
{
    use ElasticModel;
    static protected $elasticsearch;
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
        $elastic = m::mock(Elastic::class, [IndexingTestModel::class], [
            'client' => m::mock('Client', [
                'search' => [ 'hits' => [ 'hits' => [] ] ],
            ]),
            'indexName' => 'indexing-test-models',
            'documentType' => 'indexing-test-model',
        ])->shouldDeferMissing();

        DummyModel::elastic($elastic);

        $search = new SearchRequest(DummyModel::class, '*');
        $response = new Response($search);
        $driverManager = new DriverManager($response);
        $this->assertInstanceOf(EloquentDriver::class, $driverManager->driver());
    }
}
