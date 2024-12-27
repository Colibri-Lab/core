<?php

use PHPUnit\Framework\TestCase;
use Colibri\Threading\Exception;
use Colibri\Threading\ErrorCodes;

class ExceptionTest extends TestCase
{
    public function testExceptionMessage()
    {
        $exception = new Exception(ErrorCodes::UnknownProperty, 'Test message');
        $this->assertEquals('Unknown property Test message', $exception->getMessage());
    }

    public function testExceptionCode()
    {
        $exception = new Exception(ErrorCodes::UnknownProperty, 'Test message');
        $this->assertEquals(ErrorCodes::UnknownProperty, $exception->getCode());
    }
}
