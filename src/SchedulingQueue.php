<?php

namespace Redis\HyperQueue;

/**
 * Class SchedulingQueue
 * @package Redis\HyperQueue
 *
 * @inheritdoc
 */
class SchedulingQueue extends NonBlockingPriorityQueue
{
    /**
     * @param int $n
     * @return array
     */
    protected function getKeyScores($n)
    {
        $lua = <<<LUA
            local val = redis.call('zrangebyscore', KEYS[1], '-inf', KEYS[2], 'WITHSCORES', 'LIMIT' ,0, KEYS[3])
            if(next(val) ~= nil) then
                redis.call('zremrangebyrank', KEYS[1], 0, #val / 2 - 1)
            end
            return val
LUA;
        return $this->redis->eval($lua, 3, $this->name, microtime(true), $n);
    }
}