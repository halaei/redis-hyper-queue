<?php

namespace Redis\HyperQueue;

class HashTable extends RedisDS
{
    /**
     * @param array $keys
     * @return array a key-value array without keys that are not in the hash table
     */
    public function get($keys)
    {
        $keys = array_values($keys);
        $items = $this->redis->hmget($this->name, $keys);

        $keyValues = [];

        for ($i = 0; $i < count($keys); $i++) {
            if(! is_null($items[$i])) {
                $keyValues[$keys[$i]] = $items[$i];
            }
        }

        return ArraySerialization::unserializeArray($keyValues);
    }

    /**
     * @param array $keyValues key-value array
     */
    public function set($keyValues)
    {
        $this->redis->hmset($this->name, array_map(function($item) {
            return serialize($item);
        }, $keyValues));
    }
}