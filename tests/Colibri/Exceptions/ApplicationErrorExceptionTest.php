<?php

use PHPUnit\Framework\TestCase;
use Colibri\Exceptions\ApplicationErrorException;

class ApplicationErrorExceptionTest extends TestCase
{
    public function testIsException(): void
    {
        $e = new ApplicationErrorException('error occurred');
        $this->assertInstanceOf(\Exception::class, $e);
        $this->assertInstanceOf(ApplicationErrorException::class, $e);
    }

    public function testMessage(): void
    {
        $e = new ApplicationErrorException('app error');
        $this->assertEquals('app error', $e->getMessage());
    }

    public function testCode(): void
    {
        $e = new ApplicationErrorException('msg', 500);
        $this->assertEquals(500, $e->getCode());
    }

    public function testThrowAndCatch(): void
    {
        $this->expectException(ApplicationErrorException::class);
        throw new ApplicationErrorException('test');
    }

    public function testErrorCodeConstant(): void
    {
        $this->assertEquals(500, ApplicationErrorException::ErrorCode);
    }

    public function testValidationErrorConstant(): void
    {
        $this->assertEquals('Application validation error', ApplicationErrorException::ValidationError);
    }

    public function testApplicationErrorConstant(): void
    {
        $this->assertEquals('Application Error', ApplicationErrorException::ApplicationError);
    }
}
