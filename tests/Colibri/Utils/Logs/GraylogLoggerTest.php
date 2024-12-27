<?php

use PHPUnit\Framework\TestCase;
use Colibri\Utils\Logs\GraylogLogger;
use Colibri\Utils\Logs\LoggerException;

class GraylogLoggerTest extends TestCase
{
    public function testWriteLine()
    {
        $device = (object) ['server' => 'localhost', 'port' => 12201];
        $logger = new GraylogLogger(7, $device);
        $this->expectNotToPerformAssertions();
        $logger->WriteLine(1, 'Test message');
    }

    public function testInvalidDevice()
    {
        $this->expectException(LoggerException::class);
        new GraylogLogger(7, 'invalid_device');
    }
}
