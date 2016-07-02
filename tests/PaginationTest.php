<?php namespace Datashaman\ElasticModel\Tests;

use DB;

$things = [];

for($index=1; $index <= 99; $index++) {
    $things[] = [
        'category_id' => 1,
        'title' => 'Thing #'.$index,
        'description' => 'Long description for Thing #'.$index,
        'status' => 'online',
    ];
}

class PaginationTest extends TestCase
{
    public function setUp()
    {
        global $things;

        parent::setUp();

        DB::table('things')->insert($things);
        Models\Thing::import();
        sleep(1);

        $this->response = Models\Thing::search('*');
    }

    public function testOffsetMethod()
    {
        $paginator = $this->response->paginate([
            'perPage' => 3,
            'page' => 3,
        ]);
        $this->assertEquals(6, $this->response->search->definition['from']);
    }
}
