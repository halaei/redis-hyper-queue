<?php

namespace Redis\HyperQueue;

interface IQueue
{
    /**
     * Pop from the head of the queue
     *
     * @param int $n maximum number of items to be popped
     * @param int $timeout maximum time (in seconds) to wait for new items when the queue is empty
     * @return array items popped from the queue (with count <= $n)
     */
    public function pop($n = 1, $timeout = 0);
}