<?php namespace Datashaman\Elasticsearch\Model\Tests;

use Elasticsearch\Client;
use Datashaman\Elasticsearch\Model\ElasticsearchModel;
use Datashaman\Elasticsearch\Model\SearchRequest;
use Mockery as m;

class SearchRequestTestModel
{
    use ElasticsearchModel;
    protected static $elasticsearch;

    public static $indexName = 'foo';
    public static $documentType = 'bar';
}

class SearchRequestQueryBuilder
{
    public function toArray()
    {
        return [ 'foo' => 'bar' ];
    }
}

/**
 * @group passing
 */
class SearchRequestTest extends TestCase
{
    public function testSimpleQuery()
    {
        $client = SearchRequestTestModel::elasticsearch()->client(
            m::mock(Client::class)
                ->shouldReceive('search')
                ->with([
                    'index' => 'foo',
                    'type' => 'bar',
                    'body' => [
                        'query' => [
                            'query_string' => [
                                'query' => 'foo',
                            ],
                        ],
                    ],
                ])
                ->mock()
        );

        $search = new SearchRequest(SearchRequestTestModel::class, 'foo');
        $search->execute();
    }

    public function testArray()
    {
        $client = SearchRequestTestModel::elasticsearch()->client(
            m::mock(Client::class)
                ->shouldReceive('search')
                ->with([
                    'index' => 'foo',
                    'type' => 'bar',
                    'body' => [
                        'foo' => 'bar',
                    ],
                ])
                ->mock()
        );

        $search = new SearchRequest(SearchRequestTestModel::class, [ 'foo' => 'bar' ]);
        $search->execute();
    }

    public function testJsonString()
    {
        $client = SearchRequestTestModel::elasticsearch()->client(
            m::mock(Client::class)
                ->shouldReceive('search')
                ->with([
                    'index' => 'foo',
                    'type' => 'bar',
                    'body' => '{"foo":"bar"}',
                ])
                ->mock()
        );

        $search = new SearchRequest(SearchRequestTestModel::class, '{"foo":"bar"}');
        $search->execute();
    }

    public function testToArray()
    {
        $client = SearchRequestTestModel::elasticsearch()->client(
            m::mock(Client::class)
                ->shouldReceive('search')
                ->with([
                    'index' => 'foo',
                    'type' => 'bar',
                    'body' => [
                        'foo' => 'bar',
                    ],
                ])
                ->mock()
        );

        $builder = new SearchRequestQueryBuilder();
        $search = new SearchRequest(SearchRequestTestModel::class, $builder);
        $search->execute();
    }

    public function testPassOptionsToClient()
    {
        $client = SearchRequestTestModel::elasticsearch()->client(
            m::mock(Client::class)
                ->shouldReceive('search')
                ->with([
                    'index' => 'foo',
                    'type' => 'bar',
                    'body' => [
                        'query' => [
                            'query_string' => [
                                'query' => 'foo',
                            ],
                        ],
                    ],
                    'from' => 33,
                    'size' => 33,
                ])
                ->mock()
        );

        $search = new SearchRequest(SearchRequestTestModel::class, 'foo', [ 'from' => 33, 'size' => 33 ]);
        $search->execute();
    }
}
