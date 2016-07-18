<?php

namespace Datashaman\Elasticsearch\Model\Tests;

use Datashaman\Elasticsearch\Model\ElasticsearchModel;
use Datashaman\Elasticsearch\Model\Response\Result;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Schema\Blueprint;
use Schema;

class Article extends Eloquent
{
    use ElasticsearchModel;
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

        Article::create(['title' => 'Quick brown fox']);
        Article::create(['title' => 'Fast black dogs']);
        Article::create(['title' => 'Swift green frogs']);

        Article::elasticsearch()->createIndex(['force' => true]);
        Article::elasticsearch()->import();

        sleep(1);
    }

    public function tearDown()
    {
        // Article::elasticsearch()->deleteIndex();
        parent::tearDown();
    }

    public function testSearch()
    {
        $response = Article::search('fox dogs');

        $this->assertGreaterThan(0, $response->took());
        $this->assertEquals(2, $response->total());
        $this->assertGreaterThan(0, $response[0]->_score);
        $this->assertEquals('Fast black dogs', $response[0]->title);

        $this->assertEquals(['Fast black dogs', 'Quick brown fox'], $response->results()->map(function ($r) {
            return $r->title;
        })->all());

        $filtered = $response->filter(function ($r) {
            return preg_match('/^Q/', $r->title);
        });

        $this->assertEquals(1, $filtered->count());
        $this->assertEquals('Quick brown fox', $filtered->first()->title);
    }

    public function testRecords()
    {
        $response = Article::search('fox dogs');

        $this->assertEquals(2, $response->records()->count());
        $this->assertEquals(['Fast black dogs', 'Quick brown fox'], $response->records()->map(function ($r) {
            return $r->title;
        })->all());

        $lines = [];

        $response->records()->eachWithHit(function ($record, $hit) use (&$lines) {
            $lines[] = "* {$record->title}: {$hit->_score}";
        });

        $this->assertEquals([
            '* Fast black dogs: '.$response[0]->_score,
            '* Quick brown fox: '.$response[1]->_score,
        ], $lines);

        $lines = $response->records()
            ->mapWithHit(function ($record, $hit) {
                return "* {$record->title}: {$hit->_score}";
            })
            ->all();

        $this->assertEquals([
            '* Fast black dogs: '.$response[0]->_score,
            '* Quick brown fox: '.$response[1]->_score,
        ], $lines);

        $ordered = $response->records([], function ($query) {
            $query->orderBy('title', 'desc');
        })
            ->map(function ($record) {
                return $record->title;
            })
            ->all();

        $this->assertEquals([
            'Quick brown fox',
            'Fast black dogs',
        ], $ordered);
    }

    public function testPaginationAndESSorting()
    {
        /*
         * Only have 3 results so perPage is going to be 1 for all these examples.
         */
        $response = Article::search('*', [
            'sort' => [
                'title',
            ],
        ]);

        /*
         * Just so it's clear these are in the expected title order,
         */
        $this->assertEquals([
            'Fast black dogs',
            'Quick brown fox',
            'Swift green frogs',
        ], $response->map(function ($a) {
            return $a->title;
        })->all());

        /* Response can be used as an array (of the results) */
        $response = $response->perPage(1)->page(2);

        $this->assertEquals(1, count($response));

        $this->assertInstanceOf(Result::class, $response[0]);

        $article = $response[0];
        $this->assertEquals('Quick brown fox', $article->title);

        $this->assertEquals('<ul class="pagination">'.
            '<li><a href="/?page=1" rel="prev">&laquo;</a></li> '.
            '<li><a href="/?page=1">1</a></li>'.
            '<li class="active"><span>2</span></li>'.
            '<li><a href="/?page=3">3</a></li> '.
            '<li><a href="/?page=3" rel="next">&raquo;</a></li>'.
            '</ul>', $response->render());

        $this->assertEquals(3, $response->total());
        $this->assertEquals(1, $response->perPage());
        $this->assertEquals(2, $response->currentPage());
        $this->assertEquals(3, $response->lastPage());
    }

    public function testIndexConfiguration()
    {
        Article::settings(['index' => ['number_of_shards' => 1]], function ($s) {
            $s['index'] = array_merge($s['index'], [
                'number_of_replicas' => 4,
            ]);
        });

        $this->assertEquals([
            'index' => [
                'number_of_shards' => 1,
                'number_of_replicas' => 4,
            ],
        ], Article::settings()->toArray());

        Article::mappings(['dynamic' => false], function ($m) {
            $m->indexes('title', ['analyzer' => 'english', 'index_options' => 'offsets']);
        });

        $this->assertEquals([
            'article' => [
                'dynamic' => false,
                'properties' => [
                    'title' => [
                        'analyzer' => 'english',
                        'index_options' => 'offsets',
                        'type' => 'string',
                    ],
                ],
            ],
        ], Article::mappings()->toArray());

        /* The long way */
        Article::elasticsearch()->client()->indices()->delete(['index' => Article::indexName()]);
        Article::elasticsearch()->client()->indices()->create([
            'index' => Article::indexName(),
            'body' => [
                'settings' => Article::settings()->toArray(),
                'mappings' => Article::mappings()->toArray(),
            ],
        ]);

        /* The short way */
        Article::elasticsearch()->createIndex(['force' => true]);
        Article::elasticsearch()->refreshIndex();

        Article::indexName('articles-production');
        Article::documentType('post');

        $this->assertEquals('articles-production', Article::indexName());
        $this->assertEquals('post', Article::documentType());
    }
}
