<?php

namespace Redis\HyperQueue;

class HashTable extends RedisDS
{
    public function get($key)
    {
        $item = $this->redis->hget($this->name, $key);
        return is_null($item) ? null : unserialize($item);
    }

    public function set($key, $value)
    {
        $this->redis->hset($this->name, $key, serialize($value));
    }
}