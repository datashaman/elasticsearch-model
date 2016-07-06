<?php namespace Datashaman\ElasticModel\Tests;

use Datashaman\ElasticModel\Response;
use Datashaman\ElasticModel\Response\Records;
use Datashaman\ElasticModel\Response\Result;
use Datashaman\ElasticModel\Response\Results;
use Datashaman\ElasticModel\SearchRequest;
use Mockery as m;

class ResponseTest extends TestCase
{
    const RESPONSE = [
        'took' => '5',
        'timed_out' => false,
        '_shards' => [
            'one' => 'OK'
        ],
        'hits' => [
            'hits' => [
                [
                    '_id' => 1,
                    '_index' => 'things',
                    '_type' => 'type',
                    '_version' => 1,
                    '_source' => [
                        'title' => 'A Title',
                    ],
                ],
            ]
        ],
        'aggregations' => [
            'foo' => [
                'bar' => 10,
            ],
        ],
        'suggest' => [
            'my_suggest' => [
                [
                    'text' => 'foo',
                    'options' => [
                        [ 'text' => 'Foo', 'score' => 2.0 ],
                        [ 'text' => 'Bar', 'score' => 1.0 ],
                    ],
                ],
            ]
        ],
    ];

    public function testResponseAttributes()
    {
        $s = new SearchRequest(Models\Thing::class, '*');
        $search = m::mock($s, [
            'execute' => static::RESPONSE,
        ]);
        $response = new Response(Models\Thing::class, $search);

        $this->assertSame(Models\Thing::class, $response->class);
        $this->assertSame($search, $response->search());
        $this->assertSame(static::RESPONSE, $response->response());
        $this->assertSame('5', $response->took());
        $this->assertSame(false, $response->timedOut());
        $this->assertSame('OK', $response->shards()['one']);
        $this->assertSame(10, $response->aggregations()['foo']['bar']);
        $this->assertSame('Foo', $response->suggestions()['my_suggest'][0]['options'][0]['text']);
        $this->assertSame(['Foo', 'Bar'], $response->suggestions()->terms);

        $this->assertInstanceOf(Results::class, $response->results());
        $this->assertEquals(1, count($response->results()));

        $this->assertInstanceOf(Records::class, $response->records());
        $this->assertEquals(1, count($response->records()));
    }

    public function testDelegateToResults()
    {
        $s = new SearchRequest(Models\Thing::class, '*');
        $search = m::mock($s, ['execute' => static::RESPONSE]);
        $response = new Response(Models\Thing::class, $search);

        $result = $response[0];

        $this->assertInstanceOf(Result::class, $result);
    }
}
