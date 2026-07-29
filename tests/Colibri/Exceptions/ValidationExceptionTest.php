<?php

use PHPUnit\Framework\TestCase;
use Colibri\Exceptions\ValidationException;

class ValidationExceptionTest extends TestCase
{
    public function testIsException(): void
    {
        $e = new ValidationException('validation failed');
        $this->assertInstanceOf(\Exception::class, $e);
        $this->assertInstanceOf(ValidationException::class, $e);
    }

    public function testMessage(): void
    {
        $e = new ValidationException('field is required');
        $this->assertEquals('field is required', $e->getMessage());
    }

    public function testCode(): void
    {
        $e = new ValidationException('error', 422);
        $this->assertEquals(422, $e->getCode());
    }

    public function testThrowAndCatch(): void
    {
        $this->expectException(ValidationException::class);
        throw new ValidationException('test');
    }

    public function testGetExceptionDataDefault(): void
    {
        $e = new ValidationException('msg');
        $this->assertNull($e->getExceptionData());
    }

    public function testGetExceptionDataWithValue(): void
    {
        $data = ['field' => 'email', 'error' => 'invalid format'];
        $e = new ValidationException('validation error', 0, null, $data);
        $this->assertEquals($data, $e->getExceptionData());
    }

    public function testGetExceptionDataAsArray(): void
    {
        $data = ['field' => 'name'];
        $e = new ValidationException('error', 0, null, $data);
        $result = $e->getExceptionDataAsArray();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('backtrace', $result);
    }

    public function testPreviousException(): void
    {
        $previous = new \RuntimeException('original error');
        $e = new ValidationException('wrapped', 0, $previous);
        $this->assertSame($previous, $e->getPrevious());
    }
}
