<?php namespace Datashaman\ElasticModel\Tests;

use Datashaman\ElasticModel\ElasticModel;
use Datashaman\ElasticModel\SearchRequest;
use Datashaman\ElasticModel\Response;
use Datashaman\ElasticModel\Response\Records;
use DB;
use Illuminate\Database\Eloquent\Model;
use Mockery as m;
use Schema;

class RecordsTest extends TestCase
{
    const RESPONSE = [
        'hits' => [
            'total' => 1,
            'max_score' => 1.2,
            'hits' => [[
                '_id' => 1,
                '_index' => 'things',
                '_type' => 'thing',
                '_score' => 1.2,
                '_source' => [
                    'id' => 1,
                    'title' => 'Existing Thing',
                    'description' => 'This is the best thing.',
                    'status' => 'online',

                ],
            ]],
        ],
    ];

    protected $search;
    protected $response;
    protected $results;

    public function setUp()
    {
        parent::setUp();
        $this->createThings();

        $search = m::mock(new SearchRequest(Models\Thing::class, '*'), [
            'execute' => static::RESPONSE,
        ]);

        $response = new Response(Models\Thing::class, $search);
        $this->records = new Records(Models\Thing::class, $response);
    }

    public function testShouldAccessRecords()
    {
        $records = $this->records->records;
        $this->assertEquals(1, $records->count());
        $this->assertEquals('Existing Thing', $records->first()->title);
    }

    /*
    public function testArrayDelegateToResults()
    {
        $this->assertNotEmpty($this->results);
        $this->assertEquals(1, count($this->results));
        $this->assertEquals('bar', $this->results[0]->foo);
    }
     */
}
