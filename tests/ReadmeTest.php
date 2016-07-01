<?php namespace Datashaman\ElasticModel\Tests;

use Datashaman\ElasticModel\ElasticModel;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Schema\Blueprint;
use Schema;

class Article extends Eloquent
{
    use ElasticModel;
    protected $fillable = ['title'];
}

class ReadmeTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        Schema::create('articles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->timestamps();
        });

        Article::create([ 'title' => 'Quick brown fox' ]);
        Article::create([ 'title' => 'Fast black dogs' ]);
        Article::create([ 'title' => 'Swift green frogs' ]);

        Article::createIndex();
        Article::import();

        sleep(1);
    }

    public function tearDown()
    {
        Article::deleteIndex();
        parent::tearDown();
    }

    public function testSearch()
    {
        $response = Article::search('fox dogs');

        $this->assertGreaterThan(0, $response->took);
        $this->assertEquals(2, $response->results->total);
        $this->assertGreaterThan(0, $response->results[0]->score);
        $this->assertEquals('Fast black dogs', $response->results[0]->source['title']);

        $this->assertEquals([ 'Fast black dogs', 'Quick brown fox', ], $response->results->map(function ($r) { return $r->source['title']; })->all());

        $filtered = $response->results->filter(function ($r) { return preg_match('/^Q/', $r->source['title']); });

        $this->assertEquals(1, $filtered->count());
        $this->assertEquals('Quick brown fox', $filtered->first()->source['title']);

        $this->assertEquals(2, $response->records->count());
        $this->assertEquals([ 'Fast black dogs', 'Quick brown fox', ], $response->records->map(function ($r) { return $r->title; })->all());
    }
}
