<?php

use PHPUnit\Framework\TestCase;
use Colibri\Utils\Cache\Redis;

class RedisCacheTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset the singleton instance before each test
        Redis::$instance = null;
    }

    protected function tearDown(): void
    {
        Redis::$instance = null;
    }

    public function testCreateReturnsNullWhenRedisClassNotExists(): void
    {
        if (class_exists('Redis')) {
            $this->markTestSkipped('Redis class exists, cannot test null return');
        }
        $result = Redis::Create('localhost', 6379);
        $this->assertNull($result);
    }

    public function testExistsReturnsFalseWhenNoInstance(): void
    {
        Redis::$instance = null;
        $result = Redis::Exists('some_key');
        $this->assertFalse($result);
    }

    public function testReadReturnsFalseWhenNoInstance(): void
    {
        Redis::$instance = null;
        $result = Redis::Read('some_key');
        $this->assertFalse($result);
    }

    public function testDeleteReturnsFalseWhenNoInstance(): void
    {
        Redis::$instance = null;
        $result = Redis::Delete('some_key');
        $this->assertFalse($result);
    }

    public function testDisposeDoesNothingWhenNoInstance(): void
    {
        Redis::$instance = null;
        Redis::Dispose();
        $this->assertNull(Redis::$instance);
    }

    public function testWriteReturnsFalseWhenNoInstance(): void
    {
        Redis::$instance = null;
        $result = Redis::Write('key', 'value');
        $this->assertFalse($result);
    }
}
