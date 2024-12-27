<?php

use PHPUnit\Framework\TestCase;
use Colibri\Utils\Logs\LoggerException;

class LoggerExceptionTest extends TestCase
{
    public function testLoggerException()
    {
        $this->expectException(LoggerException::class);
        throw new LoggerException('Test exception');
    }
}
