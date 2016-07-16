# datashaman/elasticsearch-model

Laravel-oriented implementation of [elasticsearch-model](https://github.com/elastic/elasticsearch-rails/tree/master/elasticsearch-model).

[![Build Status](https://travis-ci.org/datashaman/elasticsearch-model.svg?branch=master)](https://travis-ci.org/datashaman/elasticsearch-model)
[![StyleCI](https://styleci.io/repos/61363628/shield?style=flat)](https://styleci.io/repos/61363628)
[![Code Climate](https://codeclimate.com/github/datashaman/elasticsearch-model/badges/gpa.svg)](https://codeclimate.com/github/datashaman/elasticsearch-model)
[![Test Coverage](https://codeclimate.com/github/datashaman/elasticsearch-model/badges/coverage.svg)](https://codeclimate.com/github/datashaman/elasticsearch-model/coverage)

## Installation

Install the package from packagist.org by editing composer.json to include the following (only published on github for now):

    {
        "repositories": [
            {
                "type": "vcs",
                "url": "https://github.com/datashaman/elasticsearch-model"
            }
        ],
        "require": {
            "datashaman/elasticsearch-model": "dev-master"
        }
    }

*NB* This is currently *ALPHA* quality software. Not for production use yet.

## Usage

Let's suppose you have an `Article` model:

```php
Schema::create('articles', function (Blueprint $table) {
    $table->increments('id');
    $table->string('title');
});

class Article extends Eloquent
{
}

Article::create([ 'title' => 'Quick brown fox' ]);
Article::create([ 'title' => 'Fast black dogs' ]);
Article::create([ 'title' => 'Swift green frogs' ]);
```

## Setup

To add the Elasticsearch integration for this model, use the `Datashaman\Elasticsearch\Model\ElasticsearchModel` trait in your class. You must also add a protected static `$elasticsearch` property for storage:

```php
use Datashaman\Elasticsearch\Model\ElasticsearchModel;

class Article extends Eloquent
{
    use ElasticsearchModel;
    protected static $elasticsearch;
}
```

This will extend the model with functionality related to Elasticsearch.

### Proxy

The package contains a big amount of class and instance methods to provide all this functionality.

To prevent polluting your model namespace, *nearly* all functionality is accessed via static method `Article::elasticsearch()`.

### Elasticsearch client

The module will setup a [client](https://github.com/elasticsearch/elasticsearch-ruby/tree/master/elasticsearch), connected to `localhost:9200`, by default. You can access and use it like any other `Elasticsearch::Client`:

```php
Article::elasticsearch()->client()->cluster()->health();
=> [ "cluster_name" => "elasticsearch", "status" => "yellow", ... ]
```

To use a client with a different configuration, set a client for the model using `Elasticsearch\ClientBuilder`:

```php
Article::elasticsearch()->client(ClientBuilder::fromConfig([ 'hosts' => [ 'api.server.org' ] ]));
```

### Importing the data

The first thing you'll want to do is import your data to the index:

```php
Article::elasticsearch()->import([ 'force' => true ]);
```

It's possible to import only records from a specific scope or query, transform the batch with the transform and preprocess options,
or re-create the index by deleting it and creating it with correct mapping with the force option -- look for examples in the method documentation.

No errors were reported during importing, so... let's search the index!

### Searching

For starters, we can try the *simple* type of search:

```php
$response = Article::search('fox dogs');

$response->took();
=> 3

$response->total();
=> 2

$response[0]->_score;
=> 0.02250402

$response[0]->title;
=> "Fast black dogs"
```

#### Search results

The returned `response` object is a rich wrapper around the JSON returned from Elasticsearch, providing access to response metadata and the actual results (*hits*).

Each *hit* is wrapped in the `Result` class.

`Result` has a dynamic getter:

* index, type, id, score and source are pulled from the top-level of the hit.
  e.g. index is hit[_index], type is hit[_type], etc
* if not one of the above, it looks for an existing item in the top-level hit.
  e.g. _version is hit[_version], etc
* if not one of the above, it looks for an existing item in hit[_source] (the document).
  e.g. title is hit[_source][title]
* if nothing resolves from above, it triggers a notice and returns null

The `response` object delegates to an internal `Collection`, so it supports all the usual methods: `map`, `filter`, `each`, etc.

```php
$response
    ->map(function ($r) { return $r->title; })
    ->all();
=> ["Fast black dogs", "Quick brown fox"]

$response
    ->filter(function ($r) { return preg_match('/^Q/', $r->title); })
    ->map(function ($r) { return $r->title; })
    ->all();
=> ["Quick brown fox"]
```

As you can see in the examples above, use the `Collection::all()` method to get a regular array.

#### Search results as database records

Instead of returning documents from Elasticsearch, the records method will return a collection of model instances, fetched from the primary database, ordered by score:

```php
$response->records()
    ->map(function ($article) { return $article->title; })
    ->all();
=> ["Fast black dogs", "Quick brown fox"]
```

The returned object is a `Collection` of model instances returned by your database, i.e. the `Eloquent` instance.

The records method returns the real instances of your model, which is useful when you want to access your model methods - at the expense of slowing down your application, of course.

In most cases, working with results coming from Elasticsearch is sufficient, and much faster.

When you want to access both the database `records` and search `results`, use the `eachWithHit` (or `mapWithHit`) iterator:

```php
$lines = [];
$response->records()->eachWithHit(function ($record, $hit) {
    $lines[] = "* {$record->title}: {$hit->_score}";
});

$lines;
=> [ "* Fast black dogs: 0.01125201", "* Quick brown fox: 0.01125201" ]

$lines = $response->records()->mapWithHit(function ($record, $hit) {
    return "* {$record->title}: {$hit->_score}";
})->all();

$lines;
=> [ "* Fast black dogs: 0.01125201", "* Quick brown fox: 0.01125201" ]
```

Note the use `Collection::all()` to convert to a regular array in the `mapWithHit` example. `Collection` methods prefer to return `Collection` instances instead of regular arrays.

The first argument to `records` is an `options` array, the second argument is a callback which is passed the query builder to modify it on-the-fly. For example, to re-order the records differently to the results (from above):

```php
$response
    ->records([], function ($query) {
        $query->orderBy('title', 'desc');
    })
    ->map(function ($article) { return $article->title; })
    ->all();

=> [ 'Quick brown fox', 'Fast black dogs' ]
```

Notice that adding an `orderBy` call to the query overrides the ordering of the records, so that it is no longer the same as the results.

#### Searching multiple models

**TODO** Implement a Facade for cross-model searching.

#### Pagination

You can implement pagination with the `from` and `size` search parameters. However, search results can be automatically paginated much like Laravel does.

```php
# Delegates to the results on page 2 with 20 per page
$response->perPage(20)->page(2);

# Records on page 2 with 20 per page; records ordered the same as results
# Order of the `page` and `perPage` calls doesn't matter
$response->page(2)->perPage(20)->records();

# Results on page 2 with (default) 15 results per page
$response->page(2)->results();

# Records on (default) page 1 with 10 records per page
$response->perPage(10)->records();
```

You have access to basic pagination methods directly on the `Response` instance:

```php
$response->page(2)->currentPage();
=> 2

$response->page(2)->lastPage();
=> 3

$response->page(2)->paginator();
=> object(Illuminate\Pagination\LengthAwarePaginator) ...
```

For the rest, you can use the `paginator` object. For example, to render the pagination links you would do this:

```php
$response->page(2)->paginator()->setPath('/articles');
$response->page(2)->paginator()->render();
=> <ul class="pagination">
    <li><a href="/articles?page=1" rel="prev">&laquo;</a></li>
    <li><a href="/articles?page=1">1</a></li>
    <li class="active"><span>2</span></li>
    <li><a href="/articles?page=3">3</a></li>
    <li><a href="/articles?page=3" rel="next">&raquo;</a></li>
</ul>
```

The rendered HTML was tidied up slightly for readability.

#### The Elasticsearch DSL

**TODO** Integrate this with a query builder.

### Index Configuration

## Attribution

Original design from [elasticsearch-model](https://github.com/elastic/elasticsearch-rails/tree/master/elasticsearch-model) which is:

* Copyright (c) 2014 Elasticsearch <http://www.elasticsearch.org>
* Licensed with Apache 2.0 license (detail in LICENSE.txt)

Changes include a rewrite of the core logic in PHP, as well as slight enhancements to accomodate Laravel and Eloquent.

## License

This package inherits the same license as its original. It is licensed under the Apache2 license, quoted below:

    Copyright (c) 2016 datashaman <marlinf@datashaman.com>

    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

    Unless required by applicable law or agreed to in writing, software
    distributed under the License is distributed on an "AS IS" BASIS,
    WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
    See the License for the specific language governing permissions and
    limitations under the License.
