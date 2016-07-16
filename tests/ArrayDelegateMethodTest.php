<?php

namespace Datashaman\Elasticsearch\Model\Tests;

use ArrayAccess;
use Datashaman\Elasticsearch\Model\ArrayDelegateMethod;

class ArrayDelegateMethodModel implements ArrayAccess
{
    use ArrayDelegateMethod;

    protected static $arrayDelegate = 'getArray';

    public $array = [
        'key' => 'value',
    ];

    public function &getArray()
    {
        $array = $this->array;
        return $array;
    }
}

class ArrayDelegateMethodTest extends TestCase
{
    public function setUp()
    {
        $this->subject = new ArrayDelegateMethodModel;
    }

    public function tearDown()
    {
        // Do nothing
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Not implemented
     */
    public function testOffsetSet()
    {
        $this->subject['new'] = 'other';
    }

    public function testOffsetExists()
    {
        $this->assertEquals(true, isset($this->subject['key']));
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessage Not implemented
     */
    public function testOffsetUnset()
    {
        unset($this->subject['key']);
    }

    public function testOffsetGet()
    {
        $this->assertEquals('value', $this->subject['key']);
    }
}
