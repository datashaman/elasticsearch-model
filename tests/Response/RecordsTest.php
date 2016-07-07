<?php namespace Datashaman\ElasticModel\Tests;

use Datashaman\ElasticModel\ElasticModel;
use Datashaman\ElasticModel\SearchRequest;
use Datashaman\ElasticModel\Response;
use Datashaman\ElasticModel\Response\Records;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Mockery as m;
use Schema;

class DummyCollection extends Collection
{
    public function __construct()
    {
        parent::__construct();
        $this->push('FOO');
    }

    public $foo = 'BAR';
}

class DummyModel
{
    use ElasticModel;
    protected static $elasticsearch;

    protected static $indexName = 'foo';
    protected static $documentType = 'bar';

    public static function whereIn($ids)
    {
        return new DummyCollection(['FOO']);
    }
}

/**
 * @group wip
 */
class RecordsTest extends TestCase
{
    protected $search;
    protected $response;
    protected $results;

    public function setUp()
    {
        parent::setUp();
        $this->createThings();

        $search = m::mock(new SearchRequest(DummyModel::class, '*'), [
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
        ]);

        $response = new Response(DummyModel::class, $search);
        $this->records = new Records(DummyModel::class, $response);
    }

    public function testShouldAccessRecords()
    {
        $records = $this->records->records;
        $this->assertEquals(1, $records->count());
        $this->assertEquals('FOO', $records->first());
    }

    public function testArrayDelegateToRecords()
    {
        $this->assertNotEmpty($this->records);
        $this->assertEquals(1, count($this->records));
        $this->assertEquals('FOO', $this->records->first());
    }

    public function testHasEachWithHitMethod()
    {
        $this->records->eachWithHit(function ($record, $hit) {
            $this->assertEquals('FOO', $record);
            $this->assertEquals('bar', $hit->foo);
        });
    }

    public function testHasMapWithHitMethod()
    {
        $this->assertEquals(['FOO---bar'], $this->records->mapWithHit(function ($record, $hit) { return "{$record}---{$hit->foo}"; })->all());
    }

    public function testShouldReturnIds()
    {
        $this->assertEquals(['1'], $this->records->ids->all());
    }
}
