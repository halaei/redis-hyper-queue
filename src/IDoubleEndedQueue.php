<?php

namespace Redis\HyperQueue;

interface IDoubleEndedQueue extends IQueue
{
    /**
     * @param array $items
     * @return int the number of items in the queue
     */
    public function push($items);

    /**
     * Push to the front of the queue, so the items will be the first things to be popped
     *
     * @param array $items
     * @return int the number of items in the queue
     */
    public function unShift($items);
}