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
        Article::elasticsearch()->deleteIndex();
        parent::tearDown();
    }

    public function testSearch()
    {
        $response = Article::search('fox dogs');

        $this->assertGreaterThan(0, $response->took());
        $this->assertEquals(2, $response->total());
        $this->assertGreaterThan(0, $response[0]->_score);
        $this->assertEquals('Fast black dogs', $response[0]->title);

        $this->assertEquals(['Fast black dogs', 'Quick brown fox'], $response->map(function ($r) {
            return $r->title;
        })->all());

        $filtered = $response->filter(function ($r) {
            return preg_match('/^Q/', $r->title);
        });

        $this->assertEquals(1, $filtered->count());
        $this->assertEquals('Quick brown fox', $filtered->first()->title);

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

        /**
         * Only have 3 results so perPage is going to be 1 for all these examples.
         */
        $response = Article::search('*', [
            'sort' => [
                'title',
            ],
        ]);

        /**
         * Just so it's clear these are in the expected title order,
         */
        $this->assertEquals([
            'Fast black dogs',
            'Quick brown fox',
            'Swift green frogs',
        ], $response->map(function ($a) { return $a->title; })->all());

        /** Response can be used as an array (of the results) */
        $page = $response->perPage(1)->page(2);

        $this->assertEquals(1, count($page));

        $this->assertInstanceOf(Result::class, $page[0]);

        /**
         * Result has a dynamic getter:
         *
         * index, type, id, score and source are pulled from the top-level of the hit.
         * e.g. index is hit[_index], type is hit[_type], etc
         *
         * if not one of the above, it looks for an existing item in the top-level hit.
         * e.g. _version is hit[_version], etc
         *
         * if not one of the above, it looks for an existing item in hit[_source] (the document).
         * e.g. title is hit[_source][title]
         *
         * if nothing resolves from above, it triggers a notice and returns null
         */
        $article = $page[0];

        $this->assertEquals('Quick brown fox', $article->title);
    }
}
