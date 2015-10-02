<?php

namespace Redis\HyperQueue;


class UniqueId extends RedisDS
{
    public function getUniqueId($n = 1)
    {
        $n = (int) $n;
        assert($n > 0);
        return $this->redis->incrby($this->name, $n);
    }
}