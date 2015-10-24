<?php

namespace HyperQueueTests\Base;

use Predis\Client;

class IntegrationTestCase extends \PHPUnit_Framework_TestCase
{
    protected static $redisServer = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'database' => 15,
        'prefix' => 'hyper-queue:test:'
    ];

    protected $childSleep;
    /**
     * @var Client
     */
    protected $redis;

    public function setUp()
    {
        parent::setUp();
        $this->childSleep = getenv('CHILD_SLEEP') ?: 1;
        $this->redis = new Client(static::$redisServer);
        $this->redis->flushdb();
    }

    public function tearDown()
    {
        $this->redis->flushdb();
        \Mockery::close();
    }

    public function fork()
    {
        $id = pcntl_fork();
        if (! $id) {
            $this->redis->disconnect();
            $this->redis->connect();
        }
        return $id;
    }
}