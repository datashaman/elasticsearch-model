<?php namespace Datashaman\ElasticModel\Tests;

use AspectMock\Test as test;
use Datashaman\ElasticModel\SearchRequest;
use Elasticsearch\Client;

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
        $client = test::double(Models\Thing::client(), ['search' => '']);
        Models\Thing::client($client);

        $s = new SearchRequest(Models\Thing::class, 'foo');
        $s->execute();

        $client->verifyMethodInvoked('search', [
            'index' => 'things',
            'type' => 'thing',
            'q' => 'foo',
        ]);
    }

    public function testSearchWithObject()
    {
        $client = test::double(Models\Thing::client(), ['search' => '']);
        Models\Thing::client($client);

        $object = new SearchingModel();

        $s = new SearchRequest(Models\Thing::class, $object);
        $s->execute();

        $client->verifyMethodInvoked('search', [
            'index' => 'things',
            'type' => 'thing',
            'body' => [
                'query' => [
                    'match' => [
                        'foo' => 'bar'
                    ],
                ],
            ],
        ]);
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

        $client = test::double(Models\Thing::client(), ['search' => '']);
        Models\Thing::client($client);

        $s = new SearchRequest(Models\Thing::class, $body);
        $s->execute();

        $client->verifyMethodInvoked('search', [
            'index' => 'things',
            'type' => 'thing',
            'body' => $body,
        ]);
    }

    public function testPassOptionsToClient()
    {
        $client = test::double(Models\Thing::client(), ['search' => '']);
        Models\Thing::client($client);

        $s = new SearchRequest(Models\Thing::class, 'foo', [ 'size' => 15 ]);
        $s->execute();

        $client->verifyMethodInvoked('search', [
            'index' => 'things',
            'type' => 'thing',
            'q' => 'foo',
            'size' => 15,
        ]);
    }
}
