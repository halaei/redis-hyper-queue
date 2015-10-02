<?php

namespace HyperQueueTests\Integration;

use HyperQueueTests\Base\IntegrationTestCase;
use Redis\HyperQueue\UniqueId;

class UniqueIdTest extends IntegrationTestCase
{
    /**
     * @var UniqueId
     */
    private $uniqueId;

    public function setUp()
    {
        parent::setUp();
        $this->uniqueId = new UniqueId($this->redis, 'unique-id');
    }

    public function test_unique_id()
    {
        $this->assertEquals(1, $this->uniqueId->getUniqueId());
        $this->assertEquals(11, $this->uniqueId->getUniqueId(10));
    }
}