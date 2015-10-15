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

    public function test_push_and_pop_1_item()
    {
        $this->queue->push(['foo']);
        $this->assertEquals(['foo'], $this->queue->pop());
    }

    public function test_push_and_pop_3_items()
    {
        $this->queue->push(['foo']);
        $this->queue->push(['bar', 'baz']);
        $this->assertEquals(['foo', 'bar'], $this->queue->pop(2));
        $this->assertEquals(['baz'], $this->queue->pop(1));
    }

    public function test_push_and_shift_3_items()
    {
        $this->queue->push(['foo']);
        $this->queue->push(['bar', 'baz']);
        $this->assertEquals(['bar', 'baz'], $this->queue->shift(2));
        $this->assertEquals(['foo'], $this->queue->shift(1));
    }

    public function test_unshift_and_shift_3_items()
    {
        $this->queue->unShift(['foo']);
        $this->queue->unShift(['bar']);
        $this->queue->unShift(['baz']);
        $this->assertEquals(['bar', 'foo'], $this->queue->shift(2));
        $this->assertEquals(['baz'], $this->queue->shift(1));
    }

    public function test_pop_from_empty_queue()
    {
        $this->assertEquals([], $this->queue->pop());
    }

    public function test_blocking_pop_from_empty_queue()
    {
        $start = time();
        $this->assertEquals([], $this->queue->pop(1, 1));
        $this->assertGreaterThan($start, time());
    }

    public function test_blocking_pop_from_a_queue_that_will_be_filled()
    {
        $pid = pcntl_fork();
        if ($pid) {
            $start = time();
            $this->assertEquals(['foo bar'], $this->queue->pop(1, 100));
            $this->assertGreaterThanOrEqual($start + $this->childSleep, time());
        } else {
            sleep($this->childSleep);
            $this->queue->push(['foo bar', 'baz']);
            exit(0);
        }
    }

    public function test_blocking_pop_2_items_from_a_queue_that_will_be_filled()
    {
        $pid = pcntl_fork();
        if ($pid) {
            $start = time();
            $this->assertEquals(['foo bar', 'baz'], $this->queue->pop(2, 100));
            $this->assertGreaterThanOrEqual($start + $this->childSleep, time());
        } else {
            sleep($this->childSleep);
            $this->queue->push(['foo bar', 'baz', 'foobar']);
            exit(0);
        }
    }

    public function test_blocking_pop_2_items_from_a_queue_that_will_be_filled_only_by_1_item()
    {
        $pid = pcntl_fork();
        if ($pid) {
            $start = time();
            $this->assertEquals(['foo bar'], $this->queue->pop(2, 100));
            $this->assertGreaterThanOrEqual($start + $this->childSleep, time());
        } else {
            sleep($this->childSleep);
            $this->queue->push(['foo bar']);
            exit(0);
        }
    }

    public function test_unShift()
    {
        $input = [1, 2, 3];
        $this->queue->unShift($input);
        $output = $this->queue->pop(3);
        $this->assertEquals(array_reverse($input), $output);
    }
}