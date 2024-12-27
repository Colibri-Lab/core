<?php

use PHPUnit\Framework\TestCase;
use Colibri\IO\FileSystem\Exception;

class ExceptionTest extends TestCase
{
    public function testExceptionMessage()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test exception');

        throw new Exception('Test exception');
    }
}
