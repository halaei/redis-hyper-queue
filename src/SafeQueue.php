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
class SafeQueue extends RedisDS implements IDoubleEndedQueue
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

    public function pop($n = 1, $timeout = 0)
    {
        $n = count($this->safeQueue->pop($n, $timeout));
        return $this->mainQueue->pop($n);
    }

    public function push($items)
    {
        $this->mainQueue->push($items);
        $this->safeQueue->push(array_fill(0, count($items), 1));
    }

    public function unShift($items)
    {
        $this->mainQueue->unShift($items);
        $this->safeQueue->unShift(array_fill(0, count($items), 1));
    }
}