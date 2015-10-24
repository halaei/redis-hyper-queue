<?php

namespace HyperQueueTests\Integration;

use HyperQueueTests\Base\IntegrationTestCase;
use Redis\HyperQueue\PriorityItem;
use Redis\HyperQueue\SchedulingQueue;

class SchedulingQueueTest extends IntegrationTestCase
{
    /**
     * @var SchedulingQueue
     */
    protected $queue;

    public function setUp()
    {
        parent::setUp();
        $this->queue = new SchedulingQueue($this->redis, 'scheduling-queue');
    }

    public function test_pop_from_empty_queue()
    {
        $this->assertEquals([], $this->queue->dequeue());
    }

    public function test_push_and_pop_one_element_at_a_time()
    {
        $now = time();
        $input = [
            new PriorityItem('first', $now - 9),
            new PriorityItem('second', $now - 8),
            new PriorityItem('third', $now - 10),
            new PriorityItem('duplicate', $now - 4),
            new PriorityItem('duplicate', $now - 5),
        ];
        $this->queue->enqueue($input);
        $this->assertEquals(1, $input[0]->id);
        $this->assertEquals(2, $input[1]->id);
        $this->assertEquals(3, $input[2]->id);
        $this->assertEquals(4, $input[3]->id);
        $this->assertEquals(5, $input[4]->id);

        $this->assertEquals([$input[2]], $this->queue->dequeue());
        $this->assertEquals([$input[0], $input[1]], $this->queue->dequeue(2));
        $this->assertEquals([$input[4], $input[3]], $this->queue->dequeue(10));
        $this->assertEquals([], $this->queue->dequeue());
    }

    public function test_pop_multiple_elements_at_once()
    {
        $now = microtime(true);
        $input = [
            new PriorityItem('first', $now - 9),
            new PriorityItem('second', $now - 8),
            new PriorityItem('third', $now - 10),
            new PriorityItem('duplicate', $now),
            new PriorityItem('duplicate', $now),
        ];
        $this->queue->enqueue($input);
        $this->assertCount(5, $this->queue->dequeue(50));
    }

    public function test_blocking_pop_from_empty_queue()
    {
        $start = time();
        $this->assertEquals([], $this->queue->dequeue(1, 1));
        $this->assertGreaterThan($start, time());
    }
}