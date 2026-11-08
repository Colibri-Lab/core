<?php

use PHPUnit\Framework\TestCase;
use Colibri\Data\Sphinx\Exception;
use Colibri\Data\DataAccessPointsException;
use Colibri\AppException;

class SphinxExceptionTest extends TestCase
{
    public function testIsException(): void
    {
        $e = new Exception('sphinx error');
        $this->assertInstanceOf(\Exception::class, $e);
        $this->assertInstanceOf(AppException::class, $e);
        $this->assertInstanceOf(DataAccessPointsException::class, $e);
        $this->assertInstanceOf(Exception::class, $e);
    }

    public function testMessage(): void
    {
        $e = new Exception('index not found');
        $this->assertEquals('index not found', $e->getMessage());
    }

    public function testCode(): void
    {
        $e = new Exception('error', 1064);
        $this->assertEquals(1064, $e->getCode());
    }

    public function testThrowAndCatch(): void
    {
        $this->expectException(Exception::class);
        throw new Exception('test');
    }
}
