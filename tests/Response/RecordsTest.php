<?php namespace Datashaman\Elasticsearch\Model\Tests;

use Datashaman\Elasticsearch\Model\ElasticsearchModel;
use Datashaman\Elasticsearch\Model\SearchRequest;
use Datashaman\Elasticsearch\Model\Response;
use Datashaman\Elasticsearch\Model\Response\Records;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Mockery as m;
use Schema;

class RecordsTestCollection extends Collection
{
    public function __construct()
    {
        parent::__construct();
        $this->push('FOO');
    }

    public $foo = 'BAR';
}

class RecordsTestModel
{
    use ElasticsearchModel;
    protected static $elasticsearch;

    protected static $indexName = 'foo';
    protected static $documentType = 'bar';

    public static function whereIn($name, $ids)
    {
        return m::mock('Builder', [
            'getQuery' => '',
            'get' => new Collection([
                (object) [
                    'id' => 1,
                    'foo' => 'BAR',
                ],
            ]),
        ]);
    }
}

/**
 * @group wip
 */
class RecordsTest extends TestCase
{
    protected $search;
    protected $response;

    public function setUp()
    {
        parent::setUp();
        // $this->createThings();

        $search = m::mock(SearchRequest::class, [ RecordsTestModel::class, '*' ], [
            'execute' => [
                'hits' => [
                    'total' => 123,
                    'max_score' => 456,
                    'hits' => [[
                        '_id' => 1,
                        'foo' => 'bar',
                    ]],
                ],
            ],
        ])->shouldDeferMissing();

        $response = new Response($search);
        $this->records = new Records($response);
    }

    public function testShouldAccessRecords()
    {
        $this->assertEquals(1, $this->records->count());
        $this->assertEquals((object) [ 'id' => 1, 'foo' => 'BAR' ], $this->records->first());
    }

    public function testHasEachWithHitMethod()
    {
        $this->records->eachWithHit(function ($record, $hit) {
            $this->assertEquals((object) [ 'id' => 1, 'foo' => 'BAR' ], $record);
            $this->assertEquals('bar', $hit->foo);
        });
    }

    public function testHasMapWithHitMethod()
    {
        $this->assertEquals(['BAR---bar'], $this->records->mapWithHit(function ($record, $hit) { return "{$record->foo}---{$hit->foo}"; })->all());
    }
}
