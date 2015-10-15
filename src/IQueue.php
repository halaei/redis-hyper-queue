<?php

namespace Redis\HyperQueue;

interface IQueue
{

    /**
     * Insert items into queue
     * @param array $items
     * @return mixed
     */
    public function enqueue(array $items);

    /**
     * Remove and return items from the queue
     *
     * @param int $n maximum number of items to be popped
     * @param int $timeout maximum time (in seconds) to wait for new items when the queue is empty
     * @return array of at most $n items popped from the queue
     */
    public function dequeue($n = 1, $timeout = 0);
}