<?php

use PHPUnit\Framework\TestCase;
use Colibri\AppException;

class AppExceptionTest extends TestCase
{
    public function testAppExceptionCanBeThrown()
    {
        $this->expectException(AppException::class);
        throw new AppException('Test exception');
    }

    public function testAppExceptionMessage()
    {
        $exception = new AppException('Test exception');
        $this->assertEquals('Test exception', $exception->getMessage());
    }
}
