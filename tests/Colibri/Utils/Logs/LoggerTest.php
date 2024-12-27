<?php

use PHPUnit\Framework\TestCase;
use Colibri\Utils\Logs\Logger;
use Colibri\Utils\Config\Config;
use Colibri\Utils\Logs\LoggerException;

class LoggerTest extends TestCase
{
    public function testCreateWithConfig()
    {
        $config = new Config([
            'type' => 'Memory',
            'level' => 7,
            'device' => []
        ]);
        $logger = Logger::Create($config);
        $this->assertInstanceOf(Logger::class, $logger);
    }

    public function testCreateWithArray()
    {
        $config = [
            'type' => 'Memory',
            'level' => 7,
            'device' => []
        ];
        $logger = Logger::Create($config);
        $this->assertInstanceOf(Logger::class, $logger);
    }

    public function testCreateWithInvalidType()
    {
        $this->expectException(LoggerException::class);
        $config = [
            'type' => 'InvalidType',
            'level' => 7,
            'device' => []
        ];
        Logger::Create($config);
    }
}
