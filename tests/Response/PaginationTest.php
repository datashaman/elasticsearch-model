<?php namespace Datashaman\ElasticModel\Tests;

use Datashaman\ElasticModel\ElasticModel;
use Datashaman\ElasticModel\Response;
use Datashaman\ElasticModel\SearchRequest;
use DB;
use Mockery as m;

class ModelClass
{
    use ElasticModel;
    protected static $elasticsearch;

    public static $indexName = 'foo';
    public static $documentType = 'bar';
    public static $perPage = 33;
}

$models = [];

for($index=1; $index <= 100; $index++) {
    $models[] = [
        'title' => 'Model #'.$index,
    ];
}

class PaginationTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->response = ModelClass::search('*');
    }

    public function tearDown()
    {
        ModelClass::resetElasticModel();
        parent::tearDown();
    }

    public function testPageParameters()
    {
        ModelClass::elastic()->client(m::mock('Client', [
            'search' => null,
        ]));

        $this->response->paginate([
            'perPage' => 3,
            'page' => 3,
        ]);

        $this->assertEquals(6, $this->response->from());
        $this->assertEquals(3, $this->response->size());
    }

    public function testFromSizeWithDefaults()
    {
        $client = m::mock('Client');

        $client->shouldReceive('search')
            ->with([
                'index' => 'foo',
                'type' => 'bar',
                'body' => [
                    'query' => [
                        'query_string' => [
                            'query' => '*',
                        ],
                    ],
                ],
                'size' => 33,
                'from' => 33,
            ]);

        ModelClass::elastic()->client($client);

        $this->assertNull(array_get($this->response->search->definition, 'size'));
        $this->assertNull(array_get($this->response->search->definition, 'from'));

        $this->response->paginate([ 'page' => 2 ])->toArray();

        $this->assertEquals(33, $this->response->search->definition['size']);
        $this->assertEquals(33, $this->response->search->definition['from']);
    }

    public function testFromSizeUsingCustom()
    {
        $client = m::mock('Client');

        $client->shouldReceive('search')
            ->with([
                'index' => 'foo',
                'type' => 'bar',
                'body' => [
                    'query' => [
                        'query_string' => [
                            'query' => '*',
                        ],
                    ],
                ],
                'size' => 9,
                'from' => 18,
            ]);

        ModelClass::elastic()->client($client);

        $this->assertNull(array_get($this->response->search->definition, 'size'));
        $this->assertNull(array_get($this->response->search->definition, 'from'));

        $this->response->paginate([ 'page' => 3, 'perPage' => 9 ])->toArray();

        $this->assertEquals(9, $this->response->search->definition['size']);
        $this->assertEquals(18, $this->response->search->definition['from']);
    }

    public function testSearchForFirstPageIfLessThanOne()
    {
        $client = m::mock('Client');

        $client->shouldReceive('search')
            ->with([
                'index' => 'foo',
                'type' => 'bar',
                'body' => [
                    'query' => [
                        'query_string' => [
                            'query' => '*',
                        ],
                    ],
                ],
                'size' => 33,
                'from' => 0,
            ]);

        ModelClass::elastic()->client($client);

        $this->assertNull(array_get($this->response->search->definition, 'size'));
        $this->assertNull(array_get($this->response->search->definition, 'from'));

        $this->response->paginate([ 'page' => "-1" ])->toArray();

        $this->assertEquals(33, $this->response->search->definition['size']);
        $this->assertEquals(0, $this->response->search->definition['from']);
    }

    public function testUseCustomName()
    {
        $client = m::mock('Client');

        $client->shouldReceive('search')
            ->with([
                'index' => 'foo',
                'type' => 'bar',
                'body' => [
                    'query' => [
                        'query_string' => [
                            'query' => '*',
                        ],
                    ],
                ],
                'size' => 10,
                'from' => 10,
            ]);

        ModelClass::elastic()->client($client);

        $this->response->paginate([ 'myPage' => 2, 'perPage' => 10, 'pageName' => 'myPage' ])->toArray();
    }

    public function testFromSizeUsingDefaultPerPage()
    {
        $this->response->page(5);
        $this->assertEquals(132, $this->response->search->definition['from']);
        $this->assertEquals(33, $this->response->search->definition['size']);
    }

    public function testFromSizeUsingPageThenPerPage()
    {
        $this->response->page(5)->perPage(3);
        $this->assertEquals(12, $this->response->search->definition['from']);
        $this->assertEquals(3, $this->response->search->definition['size']);
    }

    public function testFromSizeUsingPerPageThenPage()
    {
        $this->response->perPage(3)->page(5);
        $this->assertEquals(12, $this->response->search->definition['from']);
        $this->assertEquals(3, $this->response->search->definition['size']);
    }

    public function testReturnDefaultPageOne()
    {
        $this->response->paginate([]);
        $this->assertEquals(1, $this->response->currentPage());
    }

    public function testReturnCurrentPage()
    {
        $this->response->paginate([ 'page' => 3, 'perPage' => 9 ]);
        $this->assertEquals(3, $this->response->currentPage());
    }

    public function testReturnPerPag()
    {
        $this->response->paginate([ 'perPage' => 8 ]);
        $this->assertEquals(8, $this->response->perPage());
    }

    public function testTotal()
    {
        $response = [ 'hits' => [ 'total' => 100, 'hits' => [] ] ];
        $search = new SearchRequest(ModelClass::class, '*');
        $response = new Response($search, $response);
        $this->assertEquals(100, $response->total());
    }
}
