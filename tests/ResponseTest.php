<?php namespace Datashaman\ElasticModel\Tests;

use AspectMock\Test as test;
use Datashaman\ElasticModel\Response;
use Datashaman\ElasticModel\Response\Records;
use Datashaman\ElasticModel\Response\Result;
use Datashaman\ElasticModel\Response\Results;
use Datashaman\ElasticModel\SearchRequest;

class ResponseTest extends TestCase
{
    protected static $mockResponse = [
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
        $search = test::double($s, ['execute' => static::$mockResponse]);
        $response = new Response(Models\Thing::class, $search);

        $this->assertSame(Models\Thing::class, $response->class);
        $this->assertSame($search, $response->search);
        $this->assertSame(static::$mockResponse, $response->response->getArrayCopy());
        $this->assertSame('5', $response->took);
        $this->assertSame(false, $response->timedOut);
        $this->assertSame('OK', $response->shards->one);
        $this->assertSame(10, $response->aggregations->foo['bar']);
        $this->assertSame('Foo', $response->suggestions['my_suggest'][0]['options'][0]['text']);
        $this->assertSame(['Foo', 'Bar'], $response->suggestions->terms);

        $this->assertInstanceOf(Results::class, $response->results);
        $this->assertEquals(1, count($response->results->results));

        $this->assertInstanceOf(Records::class, $response->records);
        $this->assertEquals(1, count($response->records->records));
    }

    public function testDelegateToResults()
    {
        $s = new SearchRequest(Models\Thing::class, '*');
        $search = test::double($s, ['execute' => static::$mockResponse]);
        $response = new Response(Models\Thing::class, $search);
        $result = array_get($response, 0);

        $this->assertInstanceOf(Result::class, $result);
    }
}
