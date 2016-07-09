<?php namespace Datashaman\ElasticModel\Tests\Driver;

use Datashaman\ElasticModel\Elastic;
use Datashaman\ElasticModel\Tests\TestCase;
use Datashaman\ElasticModel\Tests\Models\Thing;
use Mockery as m;

class EloquentDriverTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->createThings();
    }

    public function testRecordsWith()
    {
        $elastic = m::mock(Elastic::class, [Thing::class], [
            'client' => m::mock([
                'search' => [
                    'hits' => [
                        'total' => 1,
                        'hits' => [[
                            '_id' => 1,
                            '_source' => [
                                'id' => 1,
                                'title' => 'Existing Thing',
                            ],
                        ]],
                    ],
                ],
            ]),
            'indexName' => 'things',
            'documentType' => 'thing',
        ])->shouldDeferMissing();

        Thing::elastic($elastic);

        $response = Thing::search('*');
        $records = $response->records([ 'with' => [ 'category' ]]);

        $this->assertTrue($records[0]->relationLoaded('category'));
    }

    public function testReorderRecordsBasedOnHits()
    {
        $elastic = m::mock(Elastic::class, [Thing::class], [
            'client' => m::mock([
                'search' => [
                    'hits' => [
                        'total' => 1,
                        'hits' => [[
                            '_id' => 2,
                            '_source' => [
                                'id' => 2,
                                'title' => 'Thing Thing',
                            ],
                        ], [
                            '_id' => 1,
                            '_source' => [
                                'id' => 1,
                                'title' => 'Existing Thing',
                            ],
                        ]],
                    ],
                ],
            ]),
            'indexName' => 'things',
            'documentType' => 'thing',
        ])->shouldDeferMissing();

        Thing::elastic($elastic);

        $thing = Thing::create([
            'title' => 'Thing Thing',
            'category_id' => 1,
        ]);

        $this->assertEquals([ 1, 2 ], Thing::all()->lists('id')->all());

        $response = Thing::search('Thing');
        $this->assertEquals([ 2, 1 ], $response->map(function ($r) { return $r->id; })->all());

        $records = $response->records();
        $this->assertEquals([ 2, 1 ], $records->map(function ($r) { return $r->id; })->all());
    }

    public function testNotReorderWhenOrderingIsPresent()
    {
        $elastic = m::mock(Elastic::class, [Thing::class], [
            'client' => m::mock([
                'search' => [
                    'hits' => [
                        'total' => 1,
                        'hits' => [[
                            '_id' => 2,
                            '_source' => [
                                'id' => 2,
                                'title' => 'Thing Thing',
                            ],
                        ], [
                            '_id' => 1,
                            '_source' => [
                                'id' => 1,
                                'title' => 'Existing Thing',
                            ],
                        ]],
                    ],
                ],
            ]),
            'indexName' => 'things',
            'documentType' => 'thing',
        ])->shouldDeferMissing();

        Thing::elastic($elastic);

        $thing = Thing::create([
            'title' => 'Thing Thing',
            'category_id' => 1,
        ]);

        $this->assertEquals([ 1, 2 ], Thing::all()->lists('id')->all());

        $response = Thing::search('Thing');
        $this->assertEquals([ 2, 1 ], $response->ids()->all());

        $records = $response->records([], function ($query) {
            $query->orderBy('id');
        });

        $this->assertEquals([ 1, 2 ], $records->map(function ($r) { return $r->id; })->all());
    }
}
