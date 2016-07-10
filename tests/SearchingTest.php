<?php namespace Datashaman\Elasticsearch\Model\Tests;

use Datashaman\Elasticsearch\Model\SearchRequest;
use Elasticsearch\Client;
use Mockery as m;

class SearchingModel
{
    public function toArray()
    {
        return [
            'query' => [
                'match' => [
                    'foo' => 'bar'
                ],
            ],
        ];
    }
}

class SearchingTest extends TestCase
{
    public function testSearchObject()
    {
        $client = m::mock('Client')
            ->shouldReceive('search')
            ->with([
                'index' => 'things',
                'type' => 'thing',
                'body' => [
                    'query' => [
                        'query_string' => [
                            'query' => 'foo',
                        ],
                    ],
                ],
                'default_operator' => 'AND',
            ])
            ->mock();

        Models\Thing::elasticsearch()->client($client);

        $searchOptions = [ 'default_operator' => 'AND' ];
        $response = Models\Thing::search('foo', $searchOptions);

        $this->assertInstanceOf(SearchRequest::class, $response->search);
        $this->assertEquals(Models\Thing::class, $response->search->class);
        $this->assertEquals('foo', $response->search->definition['body']['query']['query_string']['query']);
        $this->assertEquals($searchOptions, $response->search->options);
    }

    public function testSearchWithText()
    {
        $client = m::mock('Client')
            ->shouldReceive('search')
            ->with([
                'index' => 'things',
                'type' => 'thing',
                'body' => [
                    'query' => [
                        'query_string' => [
                            'query' => 'foo',
                        ],
                    ],
                ],
            ])
            ->mock();

        Models\Thing::elasticsearch()->client($client);

        $s = new SearchRequest(Models\Thing::class, 'foo');
        $s->execute();
    }

    public function testSearchWithObject()
    {
        $client = m::mock(Client::class)
            ->shouldReceive('search')
            ->with([
                'index' => 'things',
                'type' => 'thing',
                'body' => [
                    'query' => [
                        'match' => [
                            'foo' => 'bar',
                        ],
                    ],
                ],
            ])
            ->mock();

        Models\Thing::elasticsearch()->client($client);

        $object = new SearchingModel();
        $s = new SearchRequest(Models\Thing::class, $object);
        $s->execute();
    }

    public function testSearchWithJson()
    {
        $body = json_encode([
            'query' => [
                'match' => [
                    'foo' => 'bar'
                ],
            ],
        ]);

        $client = m::mock(Client::class)
            ->shouldReceive('search')
            ->with([
                'index' => 'things',
                'type' => 'thing',
                'body' => $body,
            ])
            ->mock();

        Models\Thing::elasticsearch()->client($client);

        $s = new SearchRequest(Models\Thing::class, $body);
        $s->execute();
    }

    public function testPassOptionsToclient()
    {
        $client = m::mock(Client::class)
            ->shouldReceive('search')
            ->with([
                'index' => 'things',
                'type' => 'thing',
                'body' => [
                    'query' => [
                        'query_string' => [
                            'query' => 'foo',
                        ],
                    ],
                ],
                'size' => 15,
            ])
            ->mock();

        Models\Thing::elasticsearch()->client($client);

        $s = new SearchRequest(Models\Thing::class, 'foo', [ 'size' => 15 ]);
        $s->execute();
    }
}
