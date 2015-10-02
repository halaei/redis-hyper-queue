<?php

namespace Redis\HyperQueue;

use Predis\Client;

abstract class RedisQueue
{
    /**
     * @var Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $name;

    /**
     * @param Client $client
     * @param string $name queue name
     */
    public function __construct(Client $client, $name)
    {
        $this->client = $client;
        $this->name = $name;
    }
}