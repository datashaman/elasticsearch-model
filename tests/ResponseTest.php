<?php

namespace Datashaman\Elasticsearch\Model\Tests;

use Datashaman\Elasticsearch\Model\Response;
use Datashaman\Elasticsearch\Model\Response\Records;
use Datashaman\Elasticsearch\Model\Response\Result;
use Datashaman\Elasticsearch\Model\SearchRequest;
use Mockery as m;

class TestResult extends Result
{
}

class ResponseTest extends TestCase
{
    protected static $mockResponse = [
        'took' => '5',
        'timed_out' => false,
        '_shards' => [
            'one' => 'OK',
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
            ],
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
                        ['text' => 'Foo', 'score' => 2.0],
                        ['text' => 'Bar', 'score' => 1.0],
                    ],
                ],
            ],
        ],
    ];

    public function testResponseAttributes()
    {
        $this->createThings();

        $search = m::mock(SearchRequest::class, [Models\Thing::class, '*'], [
            'execute' => static::$mockResponse,
        ]);

        $response = new Response($search);

        $this->assertSame($search, $response->search());
        $this->assertSame(static::$mockResponse, $response->response());
        $this->assertSame('5', $response->took());
        $this->assertSame(false, $response->timedOut());
        $this->assertSame('OK', $response->shards()['one']);
        $this->assertSame(10, $response->aggregations()['foo']['bar']);
        $this->assertSame('Foo', $response->suggestions()['my_suggest'][0]['options'][0]['text']);
        $this->assertSame(['Foo', 'Bar'], $response->suggestions()->terms()->all());

        $this->assertEquals(1, count($response));

        $result = $response[0];
        $this->assertInstanceOf(Result::class, $result);

        $records = $response->records();
        $this->assertInstanceOf(Records::class, $records);
        $this->assertEquals(1, count($records));
    }

    public function testResponseResultByClass()
    {
        $this->createThings();

        $search = m::mock(SearchRequest::class, [Models\Thing::class, '*'], [
            'execute' => static::$mockResponse,
        ]);

        $response = new Response($search, ['resultFactory' => TestResult::class]);
        $result = $response[0];
        $this->assertInstanceOf(TestResult::class, $result);
    }

    public function testResponseResultByFactory()
    {
        $this->createThings();

        $search = m::mock(SearchRequest::class, [Models\Thing::class, '*'], [
            'execute' => static::$mockResponse,
        ]);

        $response = new Response(
            $search,
            [
                'resultFactory' => function ($hit) {
                    return new TestResult($hit);
                }
            ]
        );

        $result = $response[0];
        $this->assertInstanceOf(TestResult::class, $result);
    }
}
