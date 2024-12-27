<?php

use PHPUnit\Framework\TestCase;
use Colibri\IO\FtpFileSystem\Exception;

class ExceptionTest extends TestCase
{
    public function testException()
    {
        $this->expectException(Exception::class);
        throw new Exception('Test exception');
    }

    public function testExceptionMessage()
    {
        $this->expectExceptionMessage('Test exception');
        throw new Exception('Test exception');
    }
}
