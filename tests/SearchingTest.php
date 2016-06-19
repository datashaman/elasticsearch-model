<?php namespace Datashaman\ElasticModel\Tests;

use Datashaman\ElasticModel\SearchRequest;
use Mockery;

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
        $searchOptions = [ 'default_operator' => 'AND' ];
        $response = Models\Thing::search('foo', $searchOptions);

        $search = $response->search;

        $this->assertInstanceOf(SearchRequest::class, $search);
        $this->assertEquals(Models\Thing::class, $search->class);
        $this->assertEquals('foo', $search->definition['q']);
        $this->assertEquals($searchOptions, $search->options);
    }

    public function testSearchWithText()
    {
        $client = Mockery::mock('Elasticsearch\Client')
            ->shouldReceive('search')
            ->with([
                'index' => 'things',
                'type' => 'thing',
                'q' => 'foo',
            ])
            ->mock();

        Models\Thing::client($client);

        $s = new SearchRequest(Models\Thing::class, 'foo');
        $s->execute();
    }

    public function testSearchWithObject()
    {
        $client = Mockery::mock('Elasticsearch\Client')
            ->shouldReceive('search')
            ->with([
                'index' => 'things',
                'type' => 'thing',
                'body' => [
                    'query' => [
                        'match' => [
                            'foo' => 'bar'
                        ],
                    ],
                ],
            ])
            ->mock();

        Models\Thing::client($client);

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

        $client = Mockery::mock('Elasticsearch\Client')
            ->shouldReceive('search')
            ->with([
                'index' => 'things',
                'type' => 'thing',
                'body' => $body,
            ])
            ->mock();

        Models\Thing::client($client);

        $s = new SearchRequest(Models\Thing::class, $body);
        $s->execute();
    }

    public function testPassOptionsToClient()
    {
        $client = Mockery::mock('Elasticsearch\Client')
            ->shouldReceive('search')
            ->with([
                'index' => 'things',
                'type' => 'thing',
                'q' => 'foo',
                'size' => 15,
            ])
            ->mock();

        Models\Thing::client($client);
        $s = new SearchRequest(Models\Thing::class, 'foo', [ 'size' => 15 ]);
        $s->execute();
    }
}
