<?php

namespace Datashaman\Elasticsearch\Model\Tests;

use Datashaman\Elasticsearch\Model\Elasticsearch;
use Datashaman\Elasticsearch\Model\ElasticsearchModel;
use DB;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Mockery as m;
use Schema;

class ImportingTestModel extends Model
{
    use ElasticsearchModel;
    protected static $elasticsearch;
}

class ImportingTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        Schema::create('importing_test_models', function ($table) {
            $table->increments('id');
            $table->string('title');
            $table->timestamps();
        });

        DB::table('importing_test_models')->insert([
            'title' => 'Existing Thing',
        ]);
    }

    public function testCallsClientWhenImporting()
    {
        $client = m::mock('Client', [
            'bulk' => [
                'items' => [],
            ],
        ]);

        $elastic = m::mock(Elasticsearch::class, [ImportingTestModel::class], [
            'client' => $client,
            'indexExists' => true,
            'indexName' => 'importing-test-models',
            'documentType' => 'importing-test-model',
        ])->shouldDeferMissing();

        $elastic->import();
    }

    public function testRefresh()
    {
        $client = m::mock('Client', [
            'indices->refresh' => '',
            'bulk' => [
                'items' => [],
            ],
        ]);

        $elastic = m::mock(Elasticsearch::class, [ImportingTestModel::class], [
            'client' => $client,
            'indexExists' => true,
            'indexName' => 'importing-test-models',
            'documentType' => 'importing-test-model',
        ])->shouldDeferMissing();

        $elastic->import(['refresh' => true]);
    }

    public function testReturnsNumberOfErrors()
    {
        $client = m::mock('Client', [
            'bulk' => [
                'items' => [
                    ['index' => []],
                    ['index' => ['error' => 'FAILED']],
                ],
            ],
        ]);

        $elastic = m::mock(Elasticsearch::class, [ImportingTestModel::class], [
            'client' => $client,
            'indexExists' => true,
            'indexName' => 'importing-test-models',
            'documentType' => 'importing-test-model',
        ])->shouldDeferMissing();

        $this->assertEquals(1, $elastic->import());
    }

    public function testReturnsArrayOfErrors()
    {
        $error = ['index' => ['error' => 'FAILED']];

        $elastic = m::mock(Elasticsearch::class, [ImportingTestModel::class], [
            'client' => m::mock([
                'bulk' => [
                    'items' => [
                        ['index' => []],
                        $error,
                    ],
                ],
            ]),
            'indexExists' => true,
            'indexName' => 'importing-test-models',
            'documentType' => 'importing-test-model',
        ])->shouldDeferMissing();

        $this->assertEquals([$error], $elastic->import(['return' => 'errors']));
    }

    public function testYieldResponseToCallable()
    {
        $client = m::mock('Client')
            ->shouldReceive([
                'bulk' => [
                    'items' => [
                        ['index' => []],
                        ['index' => ['error' => 'FAILED']],
                    ],
                ],
            ])
            ->mock();

        $elastic = m::mock(Elasticsearch::class, [ImportingTestModel::class], [
            'client' => $client,
            'indexExists' => true,
            'indexName' => 'importing-test-models',
            'documentType' => 'importing-test-model',
        ])->shouldDeferMissing();

        $elastic->import([], function ($response) {
            $this->assertEquals(2, count($response['items']));
        });
    }

    public function testWhenIndexDoesNotExist()
    {
        $elastic = m::mock(Elasticsearch::class, [ImportingTestModel::class], [
            'indexExists' => false,
            'indexName' => 'importing-test-models',
            'documentType' => 'importing-test-model',
        ])->shouldDeferMissing();

        $this->setExpectedException(Exception::class, "importing-test-models does not exist to be imported into. Use createIndex() or the 'force' option to create it.");
        $elastic->import();
    }

    public function testWithTheForceOption()
    {
        $client = m::mock('Client', [
            'indices->delete' => '',
            'bulk' => [
                'items' => [],
            ],
        ]);

        $elastic = m::mock(Elasticsearch::class, [ImportingTestModel::class], [
            'client' => $client,
            'indexExists' => true,
            'indexName' => 'importing-test-models',
            'documentType' => 'importing-test-model',
        ])->shouldDeferMissing();

        $elastic->import(['force' => true, 'foo' => 'bar']);
    }

    public function testCustomIndexAndType()
    {
        $encodedThing = json_encode(ImportingTestModel::first()->toIndexedArray());

        $client = m::mock('Client')
            ->shouldReceive('bulk')
            ->with([
                'index' => 'my-new-index',
                'type' => 'my-other-type',
                'body' => <<<EOF
{"index":{"_id":1}}
$encodedThing

EOF
            ])
            ->andReturn([
                'items' => [],
            ])
            ->mock();

        $elastic = m::mock(Elasticsearch::class, [ImportingTestModel::class], [
            'client' => $client,
            'indexExists' => true,
            'indexName' => 'my-new-index',
            'documentType' => 'my-other-type',
        ])->shouldDeferMissing();

        $elastic->import([
            'index' => 'my-new-index',
            'type' => 'my-other-type',
        ]);
    }

    public function testUseDefaultTransform()
    {
        $transform = function () {
            return function ($model) {
                return [
                    'index' => [
                        '_id' => $model->id,
                        'data' => $model->toIndexedArray(),
                    ],
                ];
            };
        };

        $client = m::mock('Client', [
            'bulk' => ['items' => []],
        ]);

        $elastic = m::mock(Elasticsearch::class, [ImportingTestModel::class], [
            'client' => $client,
            'indexExists' => true,
            'indexName' => 'my-new-index',
            'documentType' => 'my-other-type',
            'transform' => $transform,
        ])->shouldDeferMissing();

        $elastic->import(['index' => 'foo', 'type' => 'bar']);
    }
}
