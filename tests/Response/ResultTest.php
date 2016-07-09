<?php namespace Datashaman\ElasticModel\Tests;

use Datashaman\ElasticModel\Response\Result;


/**
 * @group passing
 */
class ResultTest extends TestCase
{
    public function testAccessToProperties()
    {
        $result = new Result([ 'foo' => 'bar', 'bar' => [ 'bam' => 'baz' ] ]);

        $this->assertEquals('bar', $result->foo);
        $this->assertEquals('baz', $result->bar['bam']);
    }

    public function testReturnIdCorrectly()
    {
        $result = new Result([ 'foo' => 'bar', '_id' => 42, '_source' => [ 'id' => 12 ] ]);

        $this->assertEquals(42, $result->id);
        $this->assertEquals(12, $result->_source['id']);
    }

    public function testReturnTypeCorrectly()
    {
        $result = new Result([ 'foo' => 'bar', '_type' => 'baz', '_source' => [ 'type' => 'BAM' ] ]);

        $this->assertEquals('baz', $result->type);
        $this->assertEquals('BAM', $result->_source['type']);
    }

    public function testDelegateToSourceWhenAvailable()
    {
        $result = new Result([ 'foo' => 'bar', '_source' => [ 'bar' => 'baz' ] ]);

        $this->assertEquals('bar', $result->foo);
        $this->assertEquals('baz', $result->_source['bar']);
        $this->assertEquals('baz', $result->bar);
    }

    /**
     * @expectedException ErrorException
     * @expectedExceptionMessage Undefined property via __get(): foo
     */
    public function testGetterEmitsError()
    {
        $result = new Result([]);
        $foo = $result->foo;
    }
}
