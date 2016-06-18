<?php namespace Datashaman\ElasticModel\Tests;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Orchestra\Testbench\TestCase as Orchestra_Testbench_TestCase;

class TestCase extends Orchestra_Testbench_TestCase
{
    protected $indexName;

    public function setUp()
    {
        parent::setUp();

        Models\Thing::indexName(substr(md5(rand()), 0, 7));
        Models\Thing::bootIndexing();

        $this->createDatabase();
        $this->createData();
    }

    protected function createDatabase()
    {
        Eloquent::unguard();

        $db = new DB;
        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $db->bootEloquent();
        $db->setAsGlobal();

        $this->schema()->create('categories', function ($table) {
            $table->increments('id');
            $table->string('title');
            $table->timestamps();
        });

        $this->schema()->create('things', function ($table) {
            $table->increments('id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('offline');
            $table->integer('category_id');
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('categories');
        });
    }

    protected function createData()
    {
        Models\Category::create([ 'title' => 'Category #1' ]);
        Models\Category::create([ 'title' => 'Category #2' ]);

        $thing = new Models\Thing;
        $thing->category()->associate(Models\Category::first());
        $thing->title = 'Existing Thing';
        $thing->description = 'This is the best thing.';
        $thing->status = 'online';
        $thing->save();
    }

    public function tearDown()
    {
        Models\Thing::deleteIndex();

        $this->schema()->drop('things');
        $this->schema()->drop('categories');

        parent::tearDown();
    }

    /**
     * Schema Helpers.
     */
    protected function schema()
    {
        return $this->connection()->getSchemaBuilder();
    }

    protected function connection()
    {
        return Eloquent::getConnectionResolver()->connection();
    }
}
