<?php

namespace HyperQueueTests\Integration;

use HyperQueueTests\Base\IntegrationTestCase;
use Redis\HyperQueue\AdvancedSchedulingQueue;
use Redis\HyperQueue\PriorityItem;
use Redis\HyperQueue\Queue;

class AdvancedSchedulingQueueTest extends IntegrationTestCase
{
    /**
     * @var AdvancedSchedulingQueue
     */
    protected $queue;

    public function setUp()
    {
        parent::setUp();
        $this->queue = new AdvancedSchedulingQueue($this->redis, 'scheduling-queue');
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

    public function test_waiting_for_2_seconds_to_pop_from_a_queue_that_has_item_expiring_sooner_than_2_seconds()
    {
        $start = time();
        $this->queue->enqueue([new PriorityItem('1 second later', $start + 1)]);
        $items = $this->queue->dequeue(1, 10);
        $this->assertCount(1, $items);
        $this->assertEquals('1 second later', $items[0]->value);
        $this->assertGreaterThanOrEqual($start + 1, time());
    }

    public function test_waiting_for_2_seconds_to_pop_from_a_queue_that_has_no_item_expiring_sooner_than_2_seconds()
    {
        $start = time();
        $this->queue->enqueue([new PriorityItem('2 seconds later', $start + 20)]);
        $items = $this->queue->dequeue(1, 2);
        $this->assertEquals([], $items);
    }

    public function test_waiting_for_2_seconds_to_multi_pop_from_a_queue_that_has_items_being_expired_in_2_seconds()
    {
        $start = time();
        $this->queue->enqueue([
            new PriorityItem('2 seconds later 1', $start + 2),
            new PriorityItem('2 seconds later 2', $start + 2),
            new PriorityItem('2 seconds later 3', $start + 2),
        ]);
        $items = $this->queue->dequeue(3, 2);
        $this->assertCount(3, $items);
        $this->assertEquals('2 seconds later 1', $items[0]->value);
        $this->assertEquals('2 seconds later 2', $items[1]->value);
        $this->assertEquals('2 seconds later 3', $items[2]->value);
        $this->assertGreaterThanOrEqual($start + 2, time());
    }

    public function test_many_child_processes_can_pop_something_from_a_queue_that_will_get_fill_at_once()
    {
        $messageQueue = new Queue($this->redis, 'fifo:message_queue');
        for ($i = 0; $i < 10; $i++) {
            if (!pcntl_fork()) {
                $items = $this->queue->dequeue(1, 100);
                if (count($items) > 0) {
                    $messageQueue->push([$items[0]->value]);
                }
                exit(0);
            }
        }
        sleep($this->childSleep);
        $items = [];
        $now = time();
        for ($i = 0; $i < 10; $i++) {
            $items[] = new PriorityItem($i, $now);
        }

        $this->queue->enqueue($items);
        for ($i = 0; $i < 10; $i++) {
            $this->assertCount(1, $messageQueue->dequeue(1, 100));
        }
    }

    public function test_many_child_processes_can_pop_something_from_a_queue_that_will_get_filled_with_delay()
    {
        $messageQueue = new Queue($this->redis, 'fifo:message_queue');
        for ($i = 0; $i < 10; $i++) {
            if (!pcntl_fork()) {
                $items = $this->queue->dequeue(1, 100);
                if (count($items) > 0) {
                    $messageQueue->push([$items[0]->value]);
                }
                exit(0);
            }
        }
        sleep($this->childSleep);
        $items = [];
        $now = time();
        for ($i = 0; $i < 10; $i++) {
            $items[] = new PriorityItem($i, $now + 2);
        }

        $this->queue->enqueue($items);
        for ($i = 0; $i < 10; $i++) {
            $this->assertCount(1, $messageQueue->dequeue(1, 100));
        }
    }

    public function test_many_child_processes_can_pop_something_from_a_queue_that_will_get_filled_gradually()
    {
        $messageQueue = new Queue($this->redis, 'fifo:message_queue');
        for ($i = 0; $i < 10; $i++) {
            if (!pcntl_fork()) {
                $items = $this->queue->dequeue(1, 100);
                if (count($items) > 0) {
                    $messageQueue->push([$items[0]->value]);
                }
                exit(0);
            }
        }
        sleep($this->childSleep);
        $items = [];
        $now = time();
        for ($i = 0; $i < 10; $i++) {
            $items[] = new PriorityItem($i, $now + $i);
        }

        $this->queue->enqueue($items);
        for ($i = 0; $i < 10; $i++) {
            $this->assertCount(1, $messageQueue->dequeue(1, 100));
        }
    }

    public function test_many_child_processes_can_multipop_from_a_queue_that_will_get_filled_gradually()
    {
        $messageQueue = new Queue($this->redis, 'fifo:message_queue');
        for ($i = 0; $i < 10; $i++) {
            if (!pcntl_fork()) {
                $items = $this->queue->dequeue(2, 100);
                if (count($items) == 2) {
                    $messageQueue->push([$items[0]->value, $items[1]->value]);
                }
                exit(0);
            }
        }
        sleep($this->childSleep);
        $items = [];
        $now = time();
        for ($i = 0; $i < 10; $i++) {
            $items[] = new PriorityItem($i, $now + $i);
            $items[] = new PriorityItem($i, $now + $i);
        }

        $this->queue->enqueue($items);
        for ($i = 0; $i < 10; $i++) {
            $this->assertCount(2, $messageQueue->dequeue(2, 100));
        }
    }

}