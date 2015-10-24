<?php

namespace HyperQueueTests\Integration;

use HyperQueueTests\Base\IntegrationTestCase;
use Redis\HyperQueue\Queue;

class QueueTest extends IntegrationTestCase
{
    /**
     * @var Queue
     */
    protected $queue;

    public function setUp()
    {
        parent::setUp();
        $this->queue = new Queue($this->redis, 'fifo');
    }

    public function test_push_and_pop_3_items()
    {
        $this->queue->push(['foo']);
        $this->queue->push(['bar', 'baz']);
        $this->assertEquals(['bar', 'baz'], $this->queue->pop(2));
        $this->assertEquals(['foo'], $this->queue->pop(1));
    }

    public function test_push_and_shift_3_items()
    {
        $this->queue->push(['foo']);
        $this->queue->push(['bar', 'baz']);
        $this->assertEquals(['foo', 'bar'], $this->queue->shift(2));
        $this->assertEquals(['baz'], $this->queue->shift(1));
    }

    public function test_unshift_and_shift_3_items()
    {
        $this->queue->unShift(['foo']);
        $this->queue->unShift(['bar']);
        $this->queue->unShift(['baz']);
        $this->assertEquals(['baz', 'bar'], $this->queue->shift(2));
        $this->assertEquals(['foo'], $this->queue->shift(1));
    }

    public function test_dequeue_from_empty_queue()
    {
        $this->assertEquals([], $this->queue->dequeue());
    }

    public function test_blocking_dequeue_from_empty_queue()
    {
        $start = time();
        $this->assertEquals([], $this->queue->dequeue(1, 1));
        $this->assertGreaterThan($start, time());
    }

    public function test_blocking_dequeue_from_a_queue_that_will_be_filled()
    {
        $pid = $this->fork();
        if ($pid) {
            $start = time();
            $this->assertEquals(['foo bar'], $this->queue->dequeue(1, 100));
            $this->assertGreaterThanOrEqual($start + $this->childSleep, time());
        } else {
            sleep($this->childSleep);
            $this->queue->enqueue(['foo bar', 'baz']);
            exit(0);
        }
    }

    public function test_blocking_dequeue_2_items_from_a_queue_that_will_be_filled()
    {
        $pid = $this->fork();
        if ($pid) {
            $start = time();
            $this->assertEquals(['foo bar', 'baz'], $this->queue->dequeue(2, 100));
            $this->assertGreaterThanOrEqual($start + $this->childSleep, time());
        } else {
            sleep($this->childSleep);
            $this->queue->enqueue(['foo bar', 'baz', 'foobar']);
            exit(0);
        }
    }

    public function test_blocking_dequeue_2_items_from_a_queue_that_will_be_filled_only_by_1_item()
    {
        $pid = $this->fork();
        if ($pid) {
            $start = time();
            $this->assertEquals(['foo bar'], $this->queue->dequeue(2, 100));
            $this->assertGreaterThanOrEqual($start + $this->childSleep, time());
        } else {
            sleep($this->childSleep);
            $this->queue->push(['foo bar']);
            exit(0);
        }
    }

    public function test_unShift_and_pop()
    {
        $input = [1, 2, 3];
        $this->queue->unShift($input);
        $output = $this->queue->pop(3);
        $this->assertEquals(array_reverse($input), $output);
    }

    public function test_enqueue_and_dequeue()
    {
        $input = [1, 2, 3];
        $this->queue->enqueue($input);
        $output = $this->queue->dequeue(3);
        $this->assertEquals(array_reverse($input), $output);
    }


}