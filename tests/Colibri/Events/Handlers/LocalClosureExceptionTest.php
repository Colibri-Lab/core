<?php

use PHPUnit\Framework\TestCase;
use Colibri\Events\Handlers\LocalClosureException;

class LocalClosureExceptionTest extends TestCase
{
    public function testIsException(): void
    {
        $e = new LocalClosureException('test message');
        $this->assertInstanceOf(\Exception::class, $e);
        $this->assertInstanceOf(LocalClosureException::class, $e);
    }

    public function testMessage(): void
    {
        $e = new LocalClosureException('handler error');
        $this->assertEquals('handler error', $e->getMessage());
    }

    public function testCode(): void
    {
        $e = new LocalClosureException('error', 42);
        $this->assertEquals(42, $e->getCode());
    }

    public function testThrowAndCatch(): void
    {
        $this->expectException(LocalClosureException::class);
        throw new LocalClosureException('test');
    }
}
