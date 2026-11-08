<?php

use PHPUnit\Framework\TestCase;
use Colibri\Utils\Logs\CallbackBasedLogger;
use Colibri\Utils\Logs\Logger;

class CallbackBasedLoggerTest extends TestCase
{
    public function testConstructorCreatesInstance(): void
    {
        $logger = new CallbackBasedLogger();
        $this->assertInstanceOf(CallbackBasedLogger::class, $logger);
        $this->assertInstanceOf(Logger::class, $logger);
    }

    public function testConstructorWithCallback(): void
    {
        $logger = new CallbackBasedLogger(7, function ($args, $level) {});
        $this->assertInstanceOf(CallbackBasedLogger::class, $logger);
    }

    public function testDeviceProperty(): void
    {
        $callback = function ($args, $level) {};
        $logger = new CallbackBasedLogger(7, $callback);
        $this->assertIsCallable($logger->device);
    }

    public function testWriteLineCallsCallback(): void
    {
        $captured = null;
        $capturedLevel = null;
        $logger = new CallbackBasedLogger(7, function ($args, $level) use (&$captured, &$capturedLevel) {
            $captured = $args;
            $capturedLevel = $level;
        });

        $logger->WriteLine(Logger::Debug, 'test message');
        $this->assertNotNull($captured);
        $this->assertEquals(Logger::Debug, $capturedLevel);
    }

    public function testWriteLinePassesMessage(): void
    {
        $captured = null;
        $logger = new CallbackBasedLogger(7, function ($args, $level) use (&$captured) {
            $captured = $args;
        });

        $logger->WriteLine(Logger::Info, 'hello world');
        $this->assertArrayHasKey('now', $captured);
    }

    public function testWriteLineIgnoresHighLevel(): void
    {
        $called = false;
        $logger = new CallbackBasedLogger(3, function ($args, $level) use (&$called) {
            $called = true;
        });

        $logger->WriteLine(7, 'debug message');
        $this->assertFalse($called);
    }

    public function testContentReturnsNull(): void
    {
        $logger = new CallbackBasedLogger();
        $this->assertNull($logger->Content());
    }

    public function testDefaultCallbackIsCallable(): void
    {
        $logger = new CallbackBasedLogger();
        $this->assertIsCallable($logger->device);
    }

    public function testPositionProperty(): void
    {
        $logger = new CallbackBasedLogger();
        $this->assertNull($logger->position);
    }

    public function testWriteLineWithArrayData(): void
    {
        $captured = null;
        $logger = new CallbackBasedLogger(7, function ($args, $level) use (&$captured) {
            $captured = $args;
        });

        $logger->WriteLine(Logger::Info, ['key1' => 'value1', 'key2' => 'value2']);
        $this->assertIsArray($captured);
        $this->assertArrayHasKey('key1', $captured);
        $this->assertArrayHasKey('now', $captured);
    }
}
