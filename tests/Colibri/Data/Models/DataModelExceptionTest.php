<?php

use PHPUnit\Framework\TestCase;
use Colibri\Data\Models\DataModelException;
use Colibri\AppException;

class DataModelExceptionTest extends TestCase
{
    public function testIsException(): void
    {
        $e = new DataModelException('data model error');
        $this->assertInstanceOf(\Exception::class, $e);
        $this->assertInstanceOf(AppException::class, $e);
        $this->assertInstanceOf(DataModelException::class, $e);
    }

    public function testMessage(): void
    {
        $e = new DataModelException('row not found');
        $this->assertEquals('row not found', $e->getMessage());
    }

    public function testCode(): void
    {
        $e = new DataModelException('error', 404);
        $this->assertEquals(404, $e->getCode());
    }

    public function testThrowAndCatch(): void
    {
        $this->expectException(DataModelException::class);
        throw new DataModelException('test');
    }
}
