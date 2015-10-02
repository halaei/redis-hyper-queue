<?php

namespace HyperQueueTests\Integration;

use HyperQueueTests\Base\IntegrationTestCase;
use Redis\HyperQueue\PriorityItem;
use Redis\HyperQueue\PriorityQueue;

class PriorityQueueTest extends IntegrationTestCase
{
    /**
     * @var PriorityQueue
     */
    protected $queue;

    public function setUp()
    {
        parent::setUp();
        $this->queue = new PriorityQueue($this->redis, 'priority-queue');
    }

    public function test_pop_from_empty_queue()
    {
        $this->assertEquals([], $this->queue->pop());
    }

    public function test_push_and_pop_one_element_at_a_time()
    {
        $input = [
            new PriorityItem('first', 1),
            new PriorityItem('second', 2),
            new PriorityItem('third', 0),
            new PriorityItem('duplicate', 6),
            new PriorityItem('duplicate', 5),
        ];
        $this->queue->push($input);
        $this->assertEquals(1, $input[0]->id);
        $this->assertEquals(2, $input[1]->id);
        $this->assertEquals(3, $input[2]->id);
        $this->assertEquals(4, $input[3]->id);
        $this->assertEquals(5, $input[4]->id);

        $this->assertEquals([$input[2]], $this->queue->pop());
        $this->assertEquals([$input[0], $input[1]], $this->queue->pop(2));
        $this->assertEquals([$input[4], $input[3]], $this->queue->pop(10));
        $this->assertEquals([], $this->queue->pop());
    }
}