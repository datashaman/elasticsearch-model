<?php namespace Datashaman\ElasticModel\Tests;

use ArrayAccess;
use Datashaman\ElasticModel\ArrayDelegate;

class ArrayDelegateModel implements ArrayAccess
{
    use ArrayDelegate;

    protected static $arrayDelegate = 'array';

    public $array = [
        'key' => 'value',
    ];
}

/**
 * @group wip
 */
class ArrayDelegateTest extends TestCase
{
    public function setUp()
    {
        $this->subject = new ArrayDelegateModel;
    }

    public function tearDown()
    {
        // Do nothing
    }

    public function testOffsetSet()
    {
        $this->subject['new'] = 'other';
        $this->assertEquals('other', $this->subject->array['new']);
    }

    public function testOffsetExists()
    {
        $this->assertEquals(true, isset($this->subject['key']));
    }

    public function testOffsetUnset()
    {
        unset($this->subject['key']);
        $this->assertEmpty($this->subject->array);
    }

    public function testOffsetGet()
    {
        $this->assertEquals('value', $this->subject['key']);
    }
}
