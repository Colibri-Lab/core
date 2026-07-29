<?php

use PHPUnit\Framework\TestCase;
use Colibri\Data\MongoDb\Exception;
use Colibri\Data\DataAccessPointsException;
use Colibri\AppException;

class MongoDbExceptionTest extends TestCase
{
    public function testIsException(): void
    {
        $e = new Exception('mongodb error');
        $this->assertInstanceOf(\Exception::class, $e);
        $this->assertInstanceOf(AppException::class, $e);
        $this->assertInstanceOf(DataAccessPointsException::class, $e);
        $this->assertInstanceOf(Exception::class, $e);
    }

    public function testMessage(): void
    {
        $e = new Exception('auth failed');
        $this->assertEquals('auth failed', $e->getMessage());
    }

    public function testCode(): void
    {
        $e = new Exception('error', 13);
        $this->assertEquals(13, $e->getCode());
    }

    public function testThrowAndCatch(): void
    {
        $this->expectException(Exception::class);
        throw new Exception('test');
    }
}
