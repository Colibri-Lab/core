<?php

use PHPUnit\Framework\TestCase;
use Colibri\Data\MySql\Exception;
use Colibri\Data\DataAccessPointsException;
use Colibri\AppException;

class MySqlExceptionTest extends TestCase
{
    public function testIsException(): void
    {
        $e = new Exception('mysql error');
        $this->assertInstanceOf(\Exception::class, $e);
        $this->assertInstanceOf(AppException::class, $e);
        $this->assertInstanceOf(DataAccessPointsException::class, $e);
        $this->assertInstanceOf(Exception::class, $e);
    }

    public function testMessage(): void
    {
        $e = new Exception('connection failed');
        $this->assertEquals('connection failed', $e->getMessage());
    }

    public function testCode(): void
    {
        $e = new Exception('error', 1045);
        $this->assertEquals(1045, $e->getCode());
    }

    public function testThrowAndCatch(): void
    {
        $this->expectException(Exception::class);
        throw new Exception('test');
    }
}
