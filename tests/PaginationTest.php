<?php namespace Datashaman\ElasticModel\Tests;

use Datashaman\ElasticModel\ElasticModel;
use DB;

class ModelClass
{
    use ElasticModel;

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
        global $models;
        parent::setUp();
        $this->response = ModelClass::search('*');
    }

    public function testPageParameters()
    {
        $client = $this->setClient([
            'search' => null,
        ], ModelClass::class);

        $this->response->paginate([
            'perPage' => 3,
            'page' => 3,
        ]);

        $this->assertEquals(6, $this->response->from);
        $this->assertEquals(3, $this->response->size);
    }

    public function testFromSizeWithDefaults()
    {
        $client = $this->setClient([
            'search' => null,
        ], ModelClass::class);

        $this->assertNull(array_get($this->response->search->definition, 'size'));
        $this->assertNull(array_get($this->response->search->definition, 'from'));

        $this->response->paginate([ 'page' => 2 ])->toArray();

        $client->verifyInvoked('search', [[
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
        ]]);

        $this->assertEquals(33, $this->response->search->definition['size']);
        $this->assertEquals(33, $this->response->search->definition['from']);
    }

    public function testFromSizeUsingCustom()
    {
        $client = $this->setClient([
            'search' => null,
        ], ModelClass::class);

        $this->assertNull(array_get($this->response->search->definition, 'size'));
        $this->assertNull(array_get($this->response->search->definition, 'from'));

        $this->response->paginate([ 'page' => 3, 'perPage' => 9 ])->toArray();

        $client->verifyInvoked('search', [[
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
        ]]);

        $this->assertEquals(9, $this->response->search->definition['size']);
        $this->assertEquals(18, $this->response->search->definition['from']);
    }

    public function testSearchForFirstPageIfLessThanOne()
    {
        $client = $this->setClient([
            'search' => null,
        ], ModelClass::class);

        $this->assertNull(array_get($this->response->search->definition, 'size'));
        $this->assertNull(array_get($this->response->search->definition, 'from'));

        $this->response->paginate([ 'page' => "-1" ])->toArray();

        $client->verifyInvoked('search', [[
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
        ]]);

        $this->assertEquals(33, $this->response->search->definition['size']);
        $this->assertEquals(0, $this->response->search->definition['from']);
    }

    public function testUseCustomName()
    {
        $client = $this->setClient([
            'search' => null,
        ], ModelClass::class);

        $this->response->paginate([ 'myPage' => 2, 'perPage' => 10, 'pageName' => 'myPage' ])->toArray();

        $client->verifyInvoked('search', [[
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
        ]]);
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
}
