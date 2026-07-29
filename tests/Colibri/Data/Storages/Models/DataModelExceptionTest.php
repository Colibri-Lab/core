<?php

use PHPUnit\Framework\TestCase;
use Colibri\Data\Storages\Models\DataModelException;

class StoragesDataModelExceptionTest extends TestCase
{
    public function testIsException(): void
    {
        $e = new DataModelException('storage model error');
        $this->assertInstanceOf(\Exception::class, $e);
        $this->assertInstanceOf(DataModelException::class, $e);
    }

    public function testMessage(): void
    {
        $e = new DataModelException('record not found');
        $this->assertEquals('record not found', $e->getMessage());
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
