<?php

use PHPUnit\Framework\TestCase;
use Colibri\Exceptions\BadMethodCallException;

class BadMethodCallExceptionTest extends TestCase
{
    public function testIsException(): void
    {
        $e = new BadMethodCallException('method not found');
        $this->assertInstanceOf(\Exception::class, $e);
        $this->assertInstanceOf(BadMethodCallException::class, $e);
    }

    public function testMessage(): void
    {
        $e = new BadMethodCallException('bad method call');
        $this->assertEquals('bad method call', $e->getMessage());
    }

    public function testDefaultCode(): void
    {
        $e = new BadMethodCallException();
        $this->assertEquals(0, $e->getCode());
    }

    public function testThrowAndCatch(): void
    {
        $this->expectException(BadMethodCallException::class);
        throw new BadMethodCallException('test');
    }
}
