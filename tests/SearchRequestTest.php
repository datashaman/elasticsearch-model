<?php namespace Datashaman\ElasticModel\Tests;

use Datashaman\ElasticModel\ElasticModel;
use Datashaman\ElasticModel\SearchRequest;

class SearchRequestTestModel
{
    use ElasticModel;

    protected static $indexName = 'foo';
    protected static $documentType = 'bar';
}

class SearchRequestQueryBuilder
{
    public function toArray()
    {
        return [ 'foo' => 'bar' ];
    }
}

class SearchRequestTest extends TestCase
{
    public function testSimpleQuery()
    {
        $client = $this->setClient([
            'search' => '',
        ], SearchRequestTestModel::class);

        $search = new SearchRequest(SearchRequestTestModel::class, 'foo');
        $search->execute();

        $client->verifyInvoked('search', [[
            'index' => 'search-request-test-models',
            'type' => 'search-request-test-model',
            'body' => [
                'query' => [
                    'query_string' => [
                        'query' => 'foo',
                    ],
                ],
            ],
        ]]);
    }

    public function testArray()
    {
        $client = $this->setClient([
            'search' => '',
        ], SearchRequestTestModel::class);

        $search = new SearchRequest(SearchRequestTestModel::class, [ 'foo' => 'bar' ]);
        $search->execute();

        $client->verifyInvoked('search', [[
            'index' => 'search-request-test-models',
            'type' => 'search-request-test-model',
            'body' => [
                'foo' => 'bar'
            ],
        ]]);
    }

    public function testJsonString()
    {
        $client = $this->setClient([
            'search' => '',
        ], SearchRequestTestModel::class);

        $search = new SearchRequest(SearchRequestTestModel::class, '{"foo":"bar"}');
        $search->execute();

        $client->verifyInvoked('search', [[
            'index' => 'search-request-test-models',
            'type' => 'search-request-test-model',
            'body' => '{"foo":"bar"}',
        ]]);
    }

    public function testToArray()
    {
        $client = $this->setClient([
            'search' => '',
        ], SearchRequestTestModel::class);

        $builder = new SearchRequestQueryBuilder();

        $search = new SearchRequest(SearchRequestTestModel::class, $builder);
        $search->execute();

        $client->verifyInvoked('search', [[
            'index' => 'search-request-test-models',
            'type' => 'search-request-test-model',
            'body' => [
                'foo' => 'bar',
            ],
        ]]);
    }

    public function testPassOptionsToClient()
    {
        $client = $this->setClient([
            'search' => '',
        ], SearchRequestTestModel::class);

        $builder = new SearchRequestQueryBuilder();

        $search = new SearchRequest(SearchRequestTestModel::class, 'foo', [ 'from' => 33, 'size' => 33 ]);
        $search->execute();

        $client->verifyInvoked('search', [[
            'index' => 'search-request-test-models',
            'type' => 'search-request-test-model',
            'body' => [
                'query' => [
                    'query_string' => [
                        'query' => 'foo',
                    ],
                ],
            ],
            'from' => 33,
            'size' => 33,
        ]]);
    }
}
