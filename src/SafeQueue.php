<?php

namespace Redis\HyperQueue;

use Predis\Client;

/**
 * Class SafeQueue
 * @package Redis\HyperQueue
 *
 * Provides the same functionality as Queue
 * It also guarantees that the popped items are always consecutive
 * Especially when multiple processes are waiting for a queue to be filled
 *
 * @inheritdoc
 */
class SafeQueue extends RedisDS implements IDoubleEndedQueue, IQueue
{
    /**
     * @var Queue
     */
    protected $mainQueue;

    /**
     * @var Queue
     */
    protected $safeQueue;

    public function __construct(Client $redis, $name)
    {
        parent::__construct($redis, $name);
        $this->mainQueue = new Queue($this->redis, $this->name);
        $this->safeQueue = new Queue($this->redis, $this->name . ':safe');
    }

    public function enqueue(array $items)
    {
        return $this->unShift($items);
    }

    public function dequeue($n = 1, $timeout = 0)
    {
        return $this->pop($n, $timeout);
    }

    public function push(array $items)
    {
        $this->mainQueue->push($items);
        return $this->safeQueue->push(array_fill(0, count($items), ''));
    }

    public function pop($n = 1, $timeout = 0)
    {
        $n = count($this->safeQueue->pop($n, $timeout));
        if($n) {
            return $this->mainQueue->pop($n, 0);

        }
        return [];
    }

    public function unShift(array $items)
    {
        $this->mainQueue->unShift($items);
        return $this->safeQueue->unShift(array_fill(0, count($items), 1));
    }

    public function shift($n = 1, $timeout = 0)
    {
        $n = count($this->safeQueue->shift($n, $timeout));
        if($n) {
            return $this->mainQueue->shift($n, 0);

        }
        return [];
    }
}