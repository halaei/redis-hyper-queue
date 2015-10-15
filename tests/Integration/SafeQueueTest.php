<?php

namespace HyperQueueTests\Integration;

use HyperQueueTests\Base\IntegrationTestCase;
use Redis\HyperQueue\Queue;
use Redis\HyperQueue\SafeQueue;

class SafeQueueTest extends IntegrationTestCase
{
    /**
     * @var SafeQueue
     */
    protected $queue;

    public function setUp()
    {
        parent::setUp();
        $this->queue = new SafeQueue($this->redis, 'fifo');
    }

    public function test_popped_items_are_consecutive_when_using_blpop()
    {
        $messageQueue = new Queue($this->redis, 'fifo:message_queue');

        for ($i = 0; $i < 2; $i++) {
            if(! pcntl_fork()) {
                $items = $this->queue->pop(2, 100);
                if (is_array($items) && count($items) == 2 && $items[1] == $items[0] + 1) {
                    $messageQueue->push(['OK']);
                } else {
                    $messageQueue->push(['ERROR']);
                }
                exit(0);
            }
        }

        sleep($this->childSleep);
        $this->queue->push([1, 2, 3, 4]);

        $this->assertEquals(['OK'], $messageQueue->pop(1, 100));
        $this->assertEquals(['OK'], $messageQueue->pop(1, 100));
    }

    public function test_safety_of_unShift()
    {
        $messageQueue = new Queue($this->redis, 'fifo:message_queue');

        for ($i = 0; $i < 2; $i++) {
            if(! pcntl_fork()) {
                $items = $this->queue->pop(2, 100);
                if (is_array($items) && count($items) == 2 && $items[1] == $items[0] - 1) {
                    $messageQueue->push(['OK']);
                } else {
                    $messageQueue->push(['ERROR']);
                }
                exit(0);
            }
        }

        sleep($this->childSleep);
        $this->queue->unShift([1, 2, 3, 4]);

        $this->assertEquals(['OK'], $messageQueue->pop(1, 100));
        $this->assertEquals(['OK'], $messageQueue->pop(1, 100));
    }
}