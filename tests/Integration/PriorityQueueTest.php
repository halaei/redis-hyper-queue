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
        $this->assertEquals([], $this->queue->dequeue());
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

    public function test_priority_queue_interaction_with_hash_table_and_list()
    {
        $this->assertEquals(0, $this->redis->hlen('priority-queue:hash-table'));
        $this->assertEquals(0, $this->redis->llen('priority-queue:list'));

        $input = [new PriorityItem('foo', 1), new PriorityItem('bar', 2)];
        $this->queue->enqueue($input);
        $this->assertEquals(2, $this->redis->hlen('priority-queue:hash-table'));
        $this->assertEquals(2, $this->redis->llen('priority-queue:list'));

        $this->queue->dequeue(1);
        $this->assertEquals(1, $this->redis->hlen('priority-queue:hash-table'));
        $this->assertEquals(1, $this->redis->llen('priority-queue:list'));
    }

    public function test_blocking_pop_from_empty_queue()
    {
        $start = time();
        $this->assertEquals([], $this->queue->dequeue(1, 1));
        $this->assertGreaterThan($start, time());
    }

    public function test_blocking_pop_from_a_queue_that_will_be_filled()
    {
        $pid = $this->fork();
        if ($pid) {
            $start = time();
            $this->assertEquals([new PriorityItem(1, 0, 1), new PriorityItem(2, 1, 2)], $this->queue->dequeue(3, 100));
            $this->assertGreaterThanOrEqual($start + $this->childSleep, time());
        } else {
            sleep($this->childSleep);
            $this->queue->enqueue([new PriorityItem(1, 0), new PriorityItem(2, 1)]);
            exit(0);
        }
    }
}