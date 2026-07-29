<?php

use PHPUnit\Framework\TestCase;
use Colibri\Data\NoSqlClient\Command;
use Colibri\Data\NoSqlClient\IConnection;
use Colibri\Data\NoSqlClient\ICommandResult;
use Colibri\Utils\Logs\Logger;

class NoSqlClientCommandTest extends TestCase
{
    public function testConstructorWithNullConnection(): void
    {
        $command = $this->getMockForAbstractClass(Command::class, [null]);
        $this->assertInstanceOf(Command::class, $command);
    }

    public function testConnectionProperty(): void
    {
        $connectionMock = $this->createMock(IConnection::class);
        $command = $this->getMockForAbstractClass(Command::class, [$connectionMock]);
        $this->assertSame($connectionMock, $command->connection);
    }

    public function testPageDefaultValue(): void
    {
        $command = $this->getMockForAbstractClass(Command::class, [null]);
        $this->assertEquals(-1, $command->page);
    }

    public function testPagesizeDefaultValue(): void
    {
        $command = $this->getMockForAbstractClass(Command::class, [null]);
        $this->assertEquals(10, $command->pagesize);
    }

    public function testParamsDefaultNull(): void
    {
        $command = $this->getMockForAbstractClass(Command::class, [null]);
        $this->assertNull($command->params);
    }

    public function testSetPageProperty(): void
    {
        $command = $this->getMockForAbstractClass(Command::class, [null]);
        $command->page = 3;
        $this->assertEquals(3, $command->page);
    }

    public function testSetPagesizeProperty(): void
    {
        $command = $this->getMockForAbstractClass(Command::class, [null]);
        $command->pagesize = 25;
        $this->assertEquals(25, $command->pagesize);
    }

    public function testSetParamsProperty(): void
    {
        $command = $this->getMockForAbstractClass(Command::class, [null]);
        $command->params = ['id' => 1];
        $this->assertEquals(['id' => 1], $command->params);
    }

    public function testUnknownPropertyReturnsNull(): void
    {
        $command = $this->getMockForAbstractClass(Command::class, [null]);
        $this->assertNull($command->unknownProp);
    }
}
