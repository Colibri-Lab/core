<?php

use PHPUnit\Framework\TestCase;
use Colibri\IO\Request\Exception;

class ExceptionTest extends TestCase
{
    public function testException()
    {
        $this->expectException(Exception::class);
        throw new Exception('Test exception');
    }
}
