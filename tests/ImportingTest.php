<?php namespace Datashaman\ElasticModel\Tests;

use AspectMock\Test as test;
use Exception;
use stdClass;

class ImportingTest extends TestCase
{
    public function testCallsClientWhenImporting()
    {
        $client = $this->getClient([
            'bulk' => ['items' => []],
            'indices' => $this->getDouble(['exists' => true]),
        ]);

        test::double(Models\Thing::class, [
            'indexExists' => true,
        ]);

        Models\Thing::import();
    }

    public function testReturnsNumberOfErrors()
    {
        $client = $this->getClient([
            'bulk' => ['items' => [['index' => []], ['index' => ['error' => 'FAILED']]]],
        ]);

        test::double(Models\Thing::class, [
            'indexExists' => true,
        ]);

        $this->assertEquals(1, Models\Thing::import());
    }

    public function testReturnsArrayOfErrors()
    {
        $error = ['index' => ['error' => 'FAILED']];

        $client = $this->getClient([
            'bulk' => ['items' => [['index' => []], $error]],
        ]);

        test::double(Models\Thing::class, [
            'indexExists' => true,
        ]);

        $this->assertEquals([$error], Models\Thing::import(['return' => 'errors']));
    }

    public function testYieldResponseToClosure()
    {
        $client = $this->getClient([
            'bulk' => ['items' => [['index' => []], ['index' => ['error' => 'FAILED']]]],
        ]);

        test::double(Models\Thing::class, [
            'indexExists' => true,
        ]);

        Models\Thing::import([], function ($response) {
            $this->assertEquals(2, count($response['items']));
        });
    }

    public function testWhenIndexDoesNotExist()
    {
        $client = $this->getClient([
            'indices' => $this->getDouble(['exists' => false]),
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("things does not exist to be imported into. Use createIndex() or the 'force' option to create it.");

        Models\Thing::import();
    }

    public function testWithTheForceOption()
    {
        Models\Thing::import(['force' => true, 'foo' => 'bar']);
    }

    public function testCustomIndexAndType()
    {
        $args = [
            'index' => 'my-new-index',
            'type' => 'my-other-type',
        ];

        /** TODO: Check arguments */
        $client = $this->getClient([
            'bulk' => ['items' => []],
            'indices' => $this->getDouble(['exists' => true]),
        ]);

        test::double(Models\Thing::class, [
            'indexName' => 'foo',
            'documentType' => 'foo',
            'indexExists' => true,
        ]);

        Models\Thing::import($args);
    }

    public function testUseDefaultTransform()
    {
        $transform = function ($a) {};

        $client = $this->getClient([
            'bulk' => ['items' => []],
        ]);

        test::double(Models\Thing::class, [
            'indexExists' => true,
            '_transform' => $transform,
        ]);

        Models\Thing::import(['index' => 'foo', 'type' => 'bar']);
    }
}
