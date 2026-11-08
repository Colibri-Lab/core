<?php

use PHPUnit\Framework\TestCase;
use Colibri\Exceptions\PermissionDeniedException;

class PermissionDeniedExceptionTest extends TestCase
{
    public function testIsException(): void
    {
        $e = new PermissionDeniedException('permission denied');
        $this->assertInstanceOf(\Exception::class, $e);
        $this->assertInstanceOf(PermissionDeniedException::class, $e);
    }

    public function testMessage(): void
    {
        $e = new PermissionDeniedException('access forbidden');
        $this->assertEquals('access forbidden', $e->getMessage());
    }

    public function testThrowAndCatch(): void
    {
        $this->expectException(PermissionDeniedException::class);
        throw new PermissionDeniedException('test');
    }

    public function testPermissionDeniedCodeConstant(): void
    {
        $this->assertEquals(403, PermissionDeniedException::PermissionDeniedCode);
    }

    public function testPermissionDeniedMessageConstant(): void
    {
        $this->assertEquals('Permission denied', PermissionDeniedException::PermissionDeniedMessage);
    }
}
