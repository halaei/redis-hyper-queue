<?php

namespace HyperQueueTests\Integration;

use HyperQueueTests\Base\IntegrationTestCase;
use Redis\HyperQueue\HashTable;

class HashTableTest extends IntegrationTestCase
{
    /**
     * @var HashTable
     */
    protected $hashTable;

    public function setUp()
    {
        parent::setUp();
        $this->hashTable = new HashTable($this->redis, 'hash-table');
    }

    public function test_get_a_non_existing_key()
    {
        $this->assertEquals([], $this->hashTable->get(['n', 'x']));
    }

    public function test_get_an_existing_key()
    {
        $this->hashTable->set(['bar' => 'foo', 'baz' => null, 'foobar' => 1]);
        $this->assertEquals(['bar' => 'foo', 'baz' => null], $this->hashTable->get(['bar', 'baz', 'foo']));
    }
}