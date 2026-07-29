<?php

use PHPUnit\Framework\TestCase;
use Colibri\Utils\Config\ConfigException;
use Colibri\AppException;

class ConfigExceptionTest extends TestCase
{
    public function testIsException(): void
    {
        $e = new ConfigException('config error');
        $this->assertInstanceOf(\Exception::class, $e);
        $this->assertInstanceOf(AppException::class, $e);
        $this->assertInstanceOf(ConfigException::class, $e);
    }

    public function testMessage(): void
    {
        $e = new ConfigException('file not found');
        $this->assertEquals('file not found', $e->getMessage());
    }

    public function testCode(): void
    {
        $e = new ConfigException('error', 404);
        $this->assertEquals(404, $e->getCode());
    }

    public function testThrowAndCatch(): void
    {
        $this->expectException(ConfigException::class);
        throw new ConfigException('test');
    }
}
