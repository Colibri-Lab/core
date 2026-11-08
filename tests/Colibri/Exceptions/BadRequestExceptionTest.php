<?php

use PHPUnit\Framework\TestCase;
use Colibri\Exceptions\BadRequestException;

class BadRequestExceptionTest extends TestCase
{
    public function testIsException(): void
    {
        $e = new BadRequestException('bad request');
        $this->assertInstanceOf(\Exception::class, $e);
        $this->assertInstanceOf(BadRequestException::class, $e);
    }

    public function testMessage(): void
    {
        $e = new BadRequestException('invalid input');
        $this->assertEquals('invalid input', $e->getMessage());
    }

    public function testThrowAndCatch(): void
    {
        $this->expectException(BadRequestException::class);
        throw new BadRequestException('test');
    }

    public function testBadRequestExceptionCodeConstant(): void
    {
        $this->assertEquals(400, BadRequestException::BadRequestExceptionCode);
    }

    public function testBadRequestExceptionMessageConstant(): void
    {
        $this->assertEquals('Bad request', BadRequestException::BadRequestExceptionMessage);
    }
}
