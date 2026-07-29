<?php

use PHPUnit\Framework\TestCase;
use Colibri\Data\PgSql\Exception;
use Colibri\Data\DataAccessPointsException;
use Colibri\AppException;

class PgSqlExceptionTest extends TestCase
{
    public function testIsException(): void
    {
        $e = new Exception('pgsql error');
        $this->assertInstanceOf(\Exception::class, $e);
        $this->assertInstanceOf(AppException::class, $e);
        $this->assertInstanceOf(DataAccessPointsException::class, $e);
        $this->assertInstanceOf(Exception::class, $e);
    }

    public function testMessage(): void
    {
        $e = new Exception('connection refused');
        $this->assertEquals('connection refused', $e->getMessage());
    }

    public function testCode(): void
    {
        $e = new Exception('error', 7);
        $this->assertEquals(7, $e->getCode());
    }

    public function testThrowAndCatch(): void
    {
        $this->expectException(Exception::class);
        throw new Exception('test');
    }
}
