<?php

namespace Redis\HyperQueue;

interface IDoubleEndedQueue
{
    /**
     * Insert items at back
     *
     * @param array $items
     * @return int the number of items in the queue
     */
    public function push(array $items);

    /**
     * Remove and return items from the back
     * @param int $n maximum number of items to be popped
     * @param int $timeout maximum time (in seconds) to wait for new items when the queue is empty
     * @return array of at most $n items popped from the queue
     */
    public function pop($n = 1, $timeout = 0);

    /**
     * Insert items at front
     *
     * @param array $items
     * @return int the number of items in the queue
     */
    public function unShift(array $items);

    /**
     * remove and return items from the front
     *
     * @param int $n maximum number of items to be popped
     * @param int $timeout maximum time (in seconds) to wait for new items when the queue is empty
     * @return array of at most $n items popped from the queue
     */
    public function shift($n = 1, $timeout = 0);
}