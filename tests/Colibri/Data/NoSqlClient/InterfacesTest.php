<?php

use PHPUnit\Framework\TestCase;
use Colibri\Data\NoSqlClient\ICommandResult;
use Colibri\Data\NoSqlClient\IConfig;
use Colibri\Data\NoSqlClient\IConnection;

class NoSqlClientInterfacesTest extends TestCase
{
    public function testICommandResultCanBeMocked(): void
    {
        $mock = $this->createMock(ICommandResult::class);
        $this->assertInstanceOf(ICommandResult::class, $mock);
    }

    public function testICommandResultErrorMethod(): void
    {
        $mock = $this->createMock(ICommandResult::class);
        $mock->method('Error')->willReturn(null);
        $this->assertNull($mock->Error());
    }

    public function testICommandResultQueryInfoMethod(): void
    {
        $mock = $this->createMock(ICommandResult::class);
        $result = (object)['affected' => 5, 'count' => 5];
        $mock->method('QueryInfo')->willReturn($result);
        $this->assertEquals(5, $mock->QueryInfo()->affected);
    }

    public function testICommandResultResultDataMethod(): void
    {
        $mock = $this->createMock(ICommandResult::class);
        $mock->method('ResultData')->willReturn([]);
        $this->assertIsArray($mock->ResultData());
    }

    public function testIConfigCanBeMocked(): void
    {
        $mock = $this->createMock(IConfig::class);
        $this->assertInstanceOf(IConfig::class, $mock);
    }

    public function testIConnectionCanBeMocked(): void
    {
        $mock = $this->createMock(IConnection::class);
        $this->assertInstanceOf(IConnection::class, $mock);
    }
}
