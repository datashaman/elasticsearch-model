<?php namespace Datashaman\ElasticModel\Tests;

use Datashaman\ElasticModel\Elasticsearch;
use DB;
use Elasticsearch\ClientBuilder;
use Eloquent;
use Mockery as m;
use Schema;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getEnvironmentSetup($app)
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    public function setUp()
    {
        parent::setUp();
        Eloquent::unguard();
        Models\Thing::resetElasticModel();
    }

    protected function createThings()
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
        m::close();
        parent::tearDown();
    }

}
