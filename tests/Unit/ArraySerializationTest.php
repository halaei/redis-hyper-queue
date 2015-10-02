<?php

namespace HyperQueueTests\Unit;

use Redis\HyperQueue\ArraySerialization;

class ArraySerializationTest extends \PHPUnit_Framework_TestCase
{
    public function test_serialization_and_unserialization()
    {
        $input = [1, '2', new \DateTime()];
        $output = ArraySerialization::unserializeArray(ArraySerialization::serializeArray($input));
        $this->assertSame($input[0], $output[0]);
        $this->assertSame($input[1], $output[1]);
        $this->assertEquals($input[2], $output[2]);
    }
}