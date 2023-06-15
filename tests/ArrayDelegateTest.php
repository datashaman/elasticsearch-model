<?php

namespace Datashaman\Elasticsearch\Model\Tests;

use ArrayAccess;
use Datashaman\Elasticsearch\Model\ArrayDelegate;

class ArrayDelegateModel implements ArrayAccess
{
    use ArrayDelegate;

    protected static $arrayDelegate = 'array';

    public $array = [
        'key' => 'value',
    ];
}

class ArrayDelegateTest extends TestCase
{
    public function setUp(): void
    {
        $this->subject = new ArrayDelegateModel;
    }

    public function tearDown(): void
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
