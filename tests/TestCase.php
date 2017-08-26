<?php

namespace Datashaman\Elasticsearch\Model\Tests;

use DB;
use Eloquent;
use Mockery as m;
use Schema;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            'Datashaman\Elasticsearch\Model\ServiceProvider',
        ];
    }

    protected function getEnvironmentSetup($app)
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('elasticsearch', [
            'hosts' => [
                'localhost:9200',
            ],
        ]);
    }

    public function setUp()
    {
        parent::setUp();
        Eloquent::unguard();
        Models\Thing::resetElasticsearch();
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
            ['title' => 'Category #1'],
            ['title' => 'Category #2'],
        ]);

        DB::table('things')->insert([
            'category_id' => 1,
            'title' => 'Existing Thing',
            'description' => 'This is the best thing.',
            'status' => 'online',
        ]);

        DB::table('things')->insert([
            'category_id' => 1,
            'title' => 'Another Thing',
            'description' => 'This is another thing.',
            'status' => 'offline',
        ]);
    }

    public function tearDown()
    {
        m::close();
        parent::tearDown();
    }
}
