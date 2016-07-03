<?php namespace Datashaman\ElasticModel\Tests;

use AspectMock\Test as test;
use Elasticsearch\Namespaces\IndicesNamespace;
use Exception;
use Illuminate\Support\Collection;
use stdClass;

class ImportingTest extends TestCase
{
    public function testCallsClientWhenImporting()
    {
        $client = $this->setClient([
            'bulk' => ['items' => []],
        ]);

        test::double(Models\Thing::elastic(), [
            'indexExists' => true,
        ]);

        $encodedThing = json_encode(Models\Thing::first()->toIndexedArray());

        Models\Thing::elastic()->import();

        $client->verifyInvoked('bulk', [[
            'index' => 'things',
            'type' => 'thing',
            'body' => <<<EOF
{"index":{"_id":1}}
$encodedThing

EOF
        ]]);
    }

    public function testReturnsNumberOfErrors()
    {
        $client = $this->setClient([
            'bulk' => ['items' => [['index' => []], ['index' => ['error' => 'FAILED']]]],
        ]);

        test::double(Models\Thing::elastic(), [
            'indexExists' => true,
        ]);

        $this->assertEquals(1, Models\Thing::elastic()->import());
    }

    public function testReturnsArrayOfErrors()
    {
        $error = ['index' => ['error' => 'FAILED']];

        $client = $this->setClient([
            'bulk' => ['items' => [['index' => []], $error]],
        ]);

        test::double(Models\Thing::elastic(), [
            'indexExists' => true,
        ]);

        $this->assertEquals([$error], Models\Thing::elastic()->import(['return' => 'errors']));
    }

    public function testYieldResponseToCallable()
    {
        $client = $this->setClient([
            'bulk' => ['items' => [['index' => []], ['index' => ['error' => 'FAILED']]]],
        ]);

        test::double(Models\Thing::elastic(), [
            'indexExists' => true,
        ]);

        Models\Thing::elastic()->import([], function ($response) {
            $this->assertEquals(2, count($response['items']));
        });
    }

    public function testWhenIndexDoesNotExist()
    {
        test::double(Models\Thing::elastic(), [
            'indexExists' => false,
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("things does not exist to be imported into. Use createIndex() or the 'force' option to create it.");

        Models\Thing::elastic()->import();
    }

    public function testWithTheForceOption()
    {
        // Models\Thing::elastic()->import(['force' => true, 'foo' => 'bar']);
    }

    public function testCustomIndexAndType()
    {
        $args = [
            'index' => 'my-new-index',
            'type' => 'my-other-type',
        ];

        /** TODO: Check arguments */
        $client = $this->setClient([
            'bulk' => ['items' => []],
        ]);

        test::double(Models\Thing::elastic(), [
            'indexName' => 'foo',
            'documentType' => 'foo',
            'indexExists' => true,
        ]);

        $encodedThing = json_encode(Models\Thing::first()->toIndexedArray());

        Models\Thing::elastic()->import($args);

        $client->verifyInvoked('bulk', [[
            'index' => 'my-new-index',
            'type' => 'my-other-type',
            'body' => <<<EOF
{"index":{"_id":1}}
$encodedThing

EOF
        ]]);
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

        $this->setClient([
            'bulk' => [ 'items' => [] ],
        ]);

        $thing = test::double(Models\Thing::elastic(), [
            'indexExists' => true,
            'transform' => $transform,
        ]);

        Models\Thing::elastic()->import(['index' => 'foo', 'type' => 'bar']);

        $thing->verifyInvoked('transform', []);
    }
}
