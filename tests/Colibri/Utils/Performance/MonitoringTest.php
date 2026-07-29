<?php

use PHPUnit\Framework\TestCase;
use Colibri\Utils\Performance\Monitoring;
use Colibri\Utils\Logs\MemoryLogger;
use Colibri\Utils\Logs\Logger;

class MonitoringTest extends TestCase
{
    private MemoryLogger $logger;

    protected function setUp(): void
    {
        $this->logger = new MemoryLogger(Logger::Debug);
    }

    public function testConstructorCreatesInstance(): void
    {
        $monitor = new Monitoring($this->logger);
        $this->assertInstanceOf(Monitoring::class, $monitor);
    }

    public function testStartAndEndTimer(): void
    {
        $monitor = new Monitoring($this->logger);
        $monitor->StartTimer('test_timer');
        usleep(1000);
        $monitor->EndTimer('test_timer');
        // No exception means success
        $this->assertTrue(true);
    }

    public function testEndNonExistentTimerDoesNotThrow(): void
    {
        $monitor = new Monitoring($this->logger);
        // Should not throw even for non-existent timer
        $monitor->EndTimer('nonexistent');
        $this->assertTrue(true);
    }

    public function testEveryTimerConstant(): void
    {
        $this->assertEquals(0, Monitoring::EveryTimer);
    }

    public function testFullStackOnlyConstant(): void
    {
        $this->assertEquals(1, Monitoring::FullStackOnly);
    }

    public function testNeverConstant(): void
    {
        $this->assertEquals(2, Monitoring::Never);
    }

    public function testLogMethod(): void
    {
        $logger = new MemoryLogger(Logger::Debug);
        $monitor = new Monitoring($logger, Logger::Debug, Monitoring::EveryTimer);
        $monitor->StartTimer('timed_op');
        usleep(1000);
        $monitor->EndTimer('timed_op');
        $monitor->Log(Logger::Debug);
        $content = $logger->Content();
        $this->assertIsArray($content);
    }

    public function testMultipleTimers(): void
    {
        $monitor = new Monitoring($this->logger);
        $monitor->StartTimer('timer1');
        $monitor->StartTimer('timer2');
        usleep(500);
        $monitor->EndTimer('timer1');
        usleep(500);
        $monitor->EndTimer('timer2');
        // No exceptions
        $this->assertTrue(true);
    }
}
