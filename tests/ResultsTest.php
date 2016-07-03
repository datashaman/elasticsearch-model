<?php namespace Datashaman\ElasticModel\Tests;

use AspectMock\Test as test;

use Datashaman\ElasticModel\ElasticModel;
use Datashaman\ElasticModel\SearchRequest;
use Datashaman\ElasticModel\Response;
use Datashaman\ElasticModel\Response\Results;

class ResultsTestModel
{
    use ElasticModel;

    protected static $indexName = 'foo';
    protected static $documentType = 'bar';
}

$response = [
    'hits' => [
        'total' => 123,
        'max_score' => 456,
        'hits' => [
            [ 'foo' => 'bar' ],
        ],
    ],
];

class ResultsTest extends TestCase
{
    protected $search;
    protected $response;
    protected $results;

    public function setUp()
    {
        global $response;

        parent::setUp();

        $this->search = test::double(new SearchRequest(ResultsTestModel::class, '*'), [
            'execute' => $response,
        ]);

        $this->response = new Response(ResultsTestModel::class, $this->search);
        $this->results = new Results(ResultsTestModel::class, $this->response);
    }

    public function testAccessResults()
    {
        $results = $this->results->results;
        $this->assertEquals(1, $results->count());
        $this->assertEquals('bar', $results->first()->foo);
    }

    public function testArrayDelegateToResults()
    {
        $this->assertNotEmpty($this->results);
        $this->assertEquals(1, count($this->results));
        $this->assertEquals('bar', $this->results[0]->foo);
    }
}
