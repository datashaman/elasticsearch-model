# elastic-model

Laravel-oriented implementation of [elasticsearch-model](https://github.com/elastic/elasticsearch-rails/tree/master/elasticsearch-model).

[![Build Status](https://travis-ci.org/datashaman/elastic-model.svg?branch=master)](https://travis-ci.org/datashaman/elastic-model)
[![Code Climate](https://codeclimate.com/github/datashaman/elastic-model/badges/gpa.svg)](https://codeclimate.com/github/datashaman/elastic-model)
[![Test Coverage](https://codeclimate.com/github/datashaman/elastic-model/badges/coverage.svg)](https://codeclimate.com/github/datashaman/elastic-model/coverage)

## Installation

Install the package from packagist.org using composer:

    composer install elastic-model

## Usage

Let's suppose you have an `Article` model:

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

## Setup

To add the Elasticsearch integration for this model, use the `Datashaman\ElasticModel\ElasticModel` trait in your class:

    use Datashaman\ElasticModel\ElasticModel;

    class Article extends Eloquent
    {
        use ElasticModel;
    }

This will extend the model with functionality related to Elasticsearch.

### Proxy

The package contains a big amount of class and instance methods to provide all this functionality.

To prevent polluting your model namespace, *nearly* all functionality is accessed via static method `Article::elastic()` in a chained manner like Elasticsearch.

### Elasticsearch client

The module will setup a [client](https://github.com/elasticsearch/elasticsearch-ruby/tree/master/elasticsearch), connected to `localhost:9200`, by default. You can access and use it like any other `Elasticsearch::Client`:

    Article::elastic()->client()->cluster()->health();
    # [ "cluster_name" => "elasticsearch", "status" => "yellow", ... ]

To use a client with a different configuration, set a client for the model using `Elasticsearch\ClientBuilder`:

    Article::elastic()->client(ClientBuilder::fromConfig([ 'hosts' => [ 'api.server.org' ] ]));

### Importing the data

The first thing you'll want to do is import your data to the index:

    Article::import();

It's possible to import only records from a specific scope or query, transform the batch with the transform and preprocess options, or re-create the index by deleting it and creating it with correct mapping with the force option -- look for examples in the method documentation.

No errors were reported during importing, so... let's search the index!

### Searching

For starters, we can try the *simple* type of search:

    $response = Article::search('fox dogs');

    $response->took;
    # 3

    $response->results->total;
    # 2

    $response->results[0]->score;
    # 0.02250402

    $response->results[0]->source['title'];
    # "Fast black dogs"

### Search results

The returned `response` object is a rich wrapper around the JSON returned from Elasticsearch, providing access to response metadata and the actual results (*hits*).

Each *hit* is wrapped in the `Result` class.

The `results` object delegates to an internal `Collection`, so it supports all the usual methods: `map`, `filter`, `each`, etc.

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
