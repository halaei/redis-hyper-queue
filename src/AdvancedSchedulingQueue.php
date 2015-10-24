<?php

namespace Redis\HyperQueue;

use Predis\Client;

class AdvancedSchedulingQueue extends RedisDS implements IQueue
{
    /**
     * @var SchedulingQueue
     */
    protected $queue;

    public function __construct(Client $redis, $name)
    {
        parent::__construct($redis, $name);
        $this->queue = new SchedulingQueue($this->redis, $this->name);
    }

    /**
     * Insert items into queue
     * @param PriorityItem[] $items
     * @return mixed
     */
    public function enqueue(array $items)
    {
        //$t0   = the time that the next item currently in the queue will get expired (inf if nothing)
        //$best = the id corresponding to $t0 (0 if nothing)
        //$n    = the number of items in $items array that are going to get expired sooner than $t0
        $first = $this->redis->zrange($this->name, 0, 0, 'WITHSCORES');
        if (count($first)) {
            $t0 = array_values($first)[0];
            $best = array_keys($first)[0];
            $n = 0;
            foreach ($items as $item) {
                if($item->score < $t0) {
                    $n++;
                }
            }
        } else {
            $t0 = INF;
            $best = 0;
            $n = count($items);
        }
        //zadd $items in the sorted set
        $this->queue->enqueue($items);
        //lpush $n flags into list "{$this->name}:$best" and make sure this list will expire soon
        if ($n) {
            $this->redis->lpush($this->name . ':' . $best, array_fill(0, $n, ''));
            $exp = $t0 != INF ? ceil($t0) : time() + 1;
            $this->redis->expireat($this->name . ':' . $best, $exp);
        }
    }

    /**
     * Remove and return items from the queue
     *
     * @param int $n maximum number of items to be popped
     * @param int $timeout maximum time (in seconds) to wait for new items when the queue is empty
     * @return PriorityItem[] array of at most $n items popped from the queue
     */
    public function dequeue($n = 1, $timeout = 0)
    {
        //do a non-blocking dequeue, return on success
        $result = $this->queue->dequeue($n, 0);
        if (count($result) || $timeout < 1) {
            return $result;
        }
        $start = microtime(true);
        //if nothing popped: while timeout is not reached do the following:
        while (microtime(true) - $start <= $timeout) {
            $result = $this->queue->dequeue($n, 0);
            if (count($result)) {
                return $result;
            }

            //find $t0 and $best as declared in $this->enqueue()
            $first = $this->redis->zrange($this->name, 0, 0, 'WITHSCORES');
            if (count($first)) {
                $t0 = array_values($first)[0];
                $best = array_keys($first)[0];
            } else {
                $t0 = $start + $timeout;
                $best = 0;
            }
            //$x = blpop "{$this->name}:$best", $t0
            //if $x: dequeue, if something is dequeued return
            $timeToWait = max(1, ceil(min($timeout, $t0 - $start)));
            $items = $this->redis->blpop([$this->name . ':' . $best], $timeToWait);
            if (is_null($items)) {
                continue;
            }
            $items = [$items[1]];
            if ($n > 1) {
                $lua = <<<LUA
                local values = redis.call('lrange', KEYS[1], 0, KEYS[2] - 1)
                redis.call('ltrim', KEYS[1], KEYS[2], - 1)
                return values
LUA;
                $remainingItems = $this->redis->eval($lua, 2, $this->name . ':' . $best, $n - 1);
                $items = array_merge($items, $remainingItems);
            }
            $result = $this->queue->dequeue(count($items));
            if (count($result)) {
                return $result;
            }
        }
        return [];
    }
}