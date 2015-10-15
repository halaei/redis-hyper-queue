<?php

namespace Redis\HyperQueue;

use Predis\Client;

/**
 * Class PriorityQueue
 * @package Redis\HyperQueue
 *
 * @inheritdoc
 */
class PriorityQueue extends NonBlockingPriorityQueue
{
    /**
     * @var Queue
     */
    protected $list;

    public function __construct(Client $redis, $name)
    {
        parent::__construct($redis, $name);
        $this->list = new Queue($this->redis, $name . ':list');
    }

    /**
     * @param PriorityItem[] $items
     * @return int the number of elements added
     * The ids of items will be set on return
     */
    public function push($items)
    {
        $n = parent::push($items);
        $this->list->push(array_fill(0, $n, 1));
        return $n;
    }

    public function pop($n = 1, $timeout = 0)
    {
        $n = count($this->list->pop($n, $timeout));
        if (!$n) {
            return [];
        }

        return parent::pop($n, 0);
    }
}