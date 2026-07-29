<?php

use PHPUnit\Framework\TestCase;
use Colibri\Utils\Logs\ModelBasedLogger;
use Colibri\Utils\Logs\Logger;
use Colibri\Utils\Logs\LoggerException;

class ModelBasedLoggerTest extends TestCase
{
    public function testConstructorWithObject(): void
    {
        $device = (object)['model' => null];
        $logger = new ModelBasedLogger(7, $device);
        $this->assertInstanceOf(ModelBasedLogger::class, $logger);
        $this->assertInstanceOf(Logger::class, $logger);
    }

    public function testConstructorWithArray(): void
    {
        $device = ['model' => null];
        $logger = new ModelBasedLogger(7, $device);
        $this->assertInstanceOf(ModelBasedLogger::class, $logger);
    }

    public function testConstructorThrowsExceptionForInvalidDevice(): void
    {
        $this->expectException(LoggerException::class);
        new ModelBasedLogger(7, 'invalid_string');
    }

    public function testConstructorThrowsExceptionForEmptyString(): void
    {
        $this->expectException(LoggerException::class);
        new ModelBasedLogger(7, '');
    }

    public function testWriteLineWithNoModelDoesNotThrow(): void
    {
        $device = (object)['model' => null];
        $logger = new ModelBasedLogger(7, $device);
        $logger->WriteLine(Logger::Info, 'test message');
        $this->assertTrue(true); // No exception
    }

    public function testWriteLineWithHighLevelIgnored(): void
    {
        $device = (object)['model' => null];
        $logger = new ModelBasedLogger(3, $device);
        $logger->WriteLine(7, 'debug message');
        $this->assertTrue(true); // No exception
    }

    public function testContentWithNoModelReturnsNull(): void
    {
        $device = (object)['model' => null];
        $logger = new ModelBasedLogger(7, $device);
        $this->assertNull($logger->Content());
    }
}
