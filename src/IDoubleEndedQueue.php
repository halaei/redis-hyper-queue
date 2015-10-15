<?php

namespace Redis\HyperQueue;

interface IDoubleEndedQueue extends IQueue
{
    /**
     * Push to the tail of the queue
     *
     * @param array $items
     * @return int the number of items in the queue
     */
    public function push($items);


    /**
     * Pop from the tail of the queue
     *
     * @param int $n maximum number of items to be popped
     * @param int $timeout maximum time (in seconds) to wait for new items when the queue is empty
     * @return array items popped from the queue (with count <= $n)
     */
    public function shift($n = 1, $timeout = 0);

    /**
     * Push to the front of the queue
     *
     * @param array $items
     * @return int the number of items in the queue
     */
    public function unShift($items);
}