<?php namespace Datashaman\ElasticModel\Tests;

use Datashaman\ElasticModel\ElasticModel;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Schema\Blueprint;
use Schema;

class Article extends Eloquent
{
    use ElasticModel;
    protected static $elasticsearch;

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

        Article::elastic()->createIndex([ 'force' => true ]);
        Article::elastic()->import();

        sleep(1);
    }

    public function tearDown()
    {
        Article::elastic()->deleteIndex();
        parent::tearDown();
    }

    public function testSearch()
    {
        $response = Article::search('fox dogs');

        $this->assertGreaterThan(0, $response->took());
        $this->assertEquals(2, $response->total());
        $this->assertGreaterThan(0, $response[0]->_score);
        $this->assertEquals('Fast black dogs', $response[0]->title);

        $this->assertEquals([ 'Fast black dogs', 'Quick brown fox', ], $response->map(function ($r) { return $r->title; })->all());

        $filtered = $response->filter(function ($r) { return preg_match('/^Q/', $r->title); });

        $this->assertEquals(1, $filtered->count());
        $this->assertEquals('Quick brown fox', $filtered->first()->title);

        $this->assertEquals(2, $response->records()->count());
        $this->assertEquals([ 'Fast black dogs', 'Quick brown fox', ], $response->records()->map(function ($r) { return $r->title; })->all());

        return;

        $lines = [];

        $response->records()->eachWithHit(function ($record, $hit) use (&$lines) {
            $lines[] = "* {$record->title}: {$hit->score}";
        });

        $this->assertEquals([
            '* Fast black dogs: '.$response[0]->score,
            '* Quick brown fox: '.$response[1]->score,
        ], $lines);

        $lines = $response->records()
            ->mapWithHit(function ($record, $hit) {
                return "* {$record->title}: {$hit->score}";
            })
            ->all();

        $this->assertEquals([
            '* Fast black dogs: '.$response[0]->score,
            '* Quick brown fox: '.$response[1]->score,
        ], $lines);
    }
}
