<?php

use PHPUnit\Framework\TestCase;
use Colibri\Utils\Cache\Mem;

class MemTest extends TestCase
{
    public function testCreate()
    {
        $memcached = Mem::Create('localhost', 11211);
        $this->assertInstanceOf(\Memcached::class, $memcached);
    }

    public function testDispose()
    {
        Mem::Create('localhost', 11211);
        Mem::Dispose();
        $this->assertNull(Mem::$instance);
    }

    public function testExists()
    {
        Mem::Create('localhost', 11211);
        Mem::Write('test_key', 'test_value');
        $this->assertTrue(Mem::Exists('test_key'));
        Mem::Delete('test_key');
        $this->assertFalse(Mem::Exists('test_key'));
    }

    public function testWriteAndRead()
    {
        Mem::Create('localhost', 11211);
        Mem::Write('test_key', 'test_value');
        $this->assertEquals('test_value', Mem::Read('test_key'));
        Mem::Delete('test_key');
    }

    public function testDelete()
    {
        Mem::Create('localhost', 11211);
        Mem::Write('test_key', 'test_value');
        Mem::Delete('test_key');
        $this->assertFalse(Mem::Exists('test_key'));
    }

    public function testList()
    {
        Mem::Create('localhost', 11211);
        Mem::Write('test_key1', 'test_value1');
        Mem::Write('test_key2', 'test_value2');
        $keys = Mem::List();
        $this->assertContains('test_key1', $keys);
        $this->assertContains('test_key2', $keys);
        Mem::Delete('test_key1');
        Mem::Delete('test_key2');
    }
}
