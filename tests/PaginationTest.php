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

        $this->response->paginate([ 'page' => 2 ]);

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
            'size' => 15,
            'from' => 15,
        ]]);

        $this->assertEquals(15, $this->response->search->definition['size']);
        $this->assertEquals(15, $this->response->search->definition['from']);
    }

    public function testFromSizeUsingCustom()
    {
        $client = $this->setClient([
            'search' => null,
        ], ModelClass::class);

        $this->assertNull(array_get($this->response->search->definition, 'size'));
        $this->assertNull(array_get($this->response->search->definition, 'from'));

        $this->response->paginate([ 'page' => 3, 'perPage' => 9 ]);

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

        $this->response->paginate([ 'page' => "-1" ]);

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
            'size' => 15,
            'from' => 0,
        ]]);

        $this->assertEquals(15, $this->response->search->definition['size']);
        $this->assertEquals(0, $this->response->search->definition['from']);
    }

    public function testUseCustomName()
    {
        $client = $this->setClient([
            'search' => null,
        ], ModelClass::class);

        $this->response->paginate([ 'myPage' => 2, 'perPage' => 10, 'pageName' => 'myPage' ]);

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
    }
}
