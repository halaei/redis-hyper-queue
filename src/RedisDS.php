<?php

namespace Redis\HyperQueue;

use Predis\Client;

abstract class RedisDS
{
    /**
     * @var Client
     */
    protected $redis;

    /**
     * @var string
     */
    protected $name;

    /**
     * @param Client $redis
     * @param string $name queue name
     */
    public function __construct(Client $redis, $name)
    {
        $this->redis = $redis;
        $this->name = $name;
    }
}