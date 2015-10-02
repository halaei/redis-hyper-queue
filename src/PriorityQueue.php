<?php

namespace Redis\HyperQueue;
use Predis\Client;

/**
 * Class PriorityQueue
 * @package Redis\HyperQueue
 *
 * @inheritdoc
 */
class PriorityQueue extends RedisDS implements IQueue
{
    /**
     * @var UniqueId
     */
    protected $autoIncrementingId;

    /**
     * @var HashTable
     */
    protected $hashTable;

    public function __construct(Client $redis, $name)
    {
        parent::__construct($redis, $name);
        $this->autoIncrementingId = new UniqueId($this->redis, $name . ':auto-incrementing-id');
        $this->hashTable = new HashTable($this->redis, $name . ':hash-table');
    }

    /**
     * @param PriorityItem[] $items
     * @return int size of the queue
     * The ids of items will be set on return
     */
    public function push($items)
    {
        $n = count($items);
        $uniqueId = $this->autoIncrementingId->getUniqueId($n);
        foreach ($items as $item) {
            $item->id = $uniqueId - $n + 1;
            $n--;
        }

        $keyValues = [];
        $keyScores = [];
        foreach ($items as $item) {
            $keyValues[$item->id] = serialize($item->value);

            $keyScores[$item->id] = $item->niceness;
        }

        $this->hashTable->set($keyValues);
        return $this->redis->zadd($this->name, $keyScores);
    }

    public function pop($n = 1, $timeout = 0)
    {
        $lua = <<<LUA
            local val = redis.call('zrange', KEYS[1], 0, KEYS[2] - 1, 'WITHSCORES')
            if(next(val) ~= nil) then
                redis.call('zremrangebyrank', KEYS[1], 0, #val / 2 - 1)
            end
            return val
LUA;
        $keyScores = $this->redis->eval($lua, 2, $this->name, $n);
        if (!count($keyScores)) {
            return [];
        }
        $keys = [];
        $scores = [];
        for($i = 0; $i < count($keyScores); $i += 2)
        {
            $keys[] = $keyScores[$i];
            $scores[$keyScores[$i]] = $keyScores[$i + 1];
        }
        $keyValues = $this->hashTable->get($keys);
        $this->hashTable->delete($keys);

        $items = [];
        foreach ($keyValues as $key => $value) {
            $items[] = new PriorityItem(unserialize($value), $scores[$key], $key);
        }

        return $items;
    }
}