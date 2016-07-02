<?php namespace Datashaman\ElasticModel\Tests;

use AspectMock\Test as test;
use DB;
use Elasticsearch\ClientBuilder;
use Datashaman\ElasticModel\Elasticsearch;
use Schema;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected $indexName;

    public function setUp()
    {
        parent::setUp();

        Models\Thing::resetElasticModel();
        Models\Thing::bootIndexing();

        $this->createDatabase();
    }

    protected function getEnvironmentSetup($app)
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function createDatabase()
    {
        Schema::create('categories', function ($table) {
            $table->increments('id');
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('things', function ($table) {
            $table->increments('id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('offline');
            $table->integer('category_id');
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('categories');
        });

        DB::table('categories')->insert([
            [ 'title' => 'Category #1' ],
            [ 'title' => 'Category #2' ],
        ]);

        DB::table('things')->insert([
            'category_id' => 1,
            'title' => 'Existing Thing',
            'description' => 'This is the best thing.',
            'status' => 'online',
        ]);
    }

    public function tearDown()
    {
        test::clean();

        Schema::drop('things');
        Schema::drop('categories');

        parent::tearDown();
    }

    protected function setClient($expectations, $class=Models\Thing::class)
    {
        $object = ClientBuilder::create()->build();
        $client = test::double($object, $expectations);
        $class::elastic()->client($client);
        return $client;
    }
}
