<?php namespace Datashaman\ElasticModel\Tests\Driver;

use Datashaman\ElasticModel\Elastic;
use Datashaman\ElasticModel\Tests\TestCase;
use Datashaman\ElasticModel\Tests\Models\Thing;
use Mockery as m;

class EloquentDriverTest extends TestCase
{
    public function testRecordsWith()
    {
        $this->createThings();

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
}
