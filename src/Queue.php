<?php

namespace Redis\HyperQueue;

/**
 * Class Queue
 *
 * FIFO Queue/Stack
 * @package Redis\HyperQueue
 *
 * @inheritdoc
 */
class Queue extends RedisDS implements IDoubleEndedQueue, IQueue
{

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
        $items = ArraySerialization::serializeArray($items);
        return $this->redis->rpush($this->name, $items);
    }

    public function pop($n = 1, $timeout = 0)
    {
        $lua = <<<LUA
                local values = redis.call('lrange', KEYS[1], -KEYS[2], -1)
                redis.call('ltrim', KEYS[1], 0, -1 -KEYS[2])
                return values
LUA;

        $items = $this->redis->eval($lua, 2, $this->name, $n);
        if (!count($items) && $timeout) {
            $items = $this->redis->brpop([$this->name], $timeout);
            if (is_null($items)) {
                return [];
            }
            $items = [$items[1]];
            if ($n > 1) {
                $remainingItems = $this->redis->eval($lua, 2, $this->name, $n - 1);
                $items = array_merge($items, $remainingItems);
            }
        }
        return ArraySerialization::unserializeArray($items);
    }

    public function unShift(array $items)
    {
        $items = ArraySerialization::serializeArray($items);
        return $this->redis->lpush($this->name, $items);
    }

    public function shift($n = 1, $timeout = 0)
    {
        $lua = <<<LUA
                local values = redis.call('lrange', KEYS[1], 0, KEYS[2] - 1)
                redis.call('ltrim', KEYS[1], KEYS[2], - 1)
                return values
LUA;

        $items = $this->redis->eval($lua, 2, $this->name, $n);
        if (!count($items) && $timeout) {
            $items = $this->redis->blpop([$this->name], $timeout);
            if (is_null($items)) {
                return [];
            }
            $items = [$items[1]];
            if ($n > 1) {
                $remainingItems = $this->redis->eval($lua, 2, $this->name, $n - 1);
                $items = array_merge($items, $remainingItems);
            }
        }
        return ArraySerialization::unserializeArray($items);
    }
}