<?php

use PHPUnit\Framework\TestCase;
use Colibri\Data\SqlClient\IConnection;

class IConnectionTest extends TestCase
{

    public function testOpen()
    {
        $mock = $this->createMock(IConnection::class);
        $mock->method('Open')->willReturn(true);

        $this->assertTrue($mock->Open());
    }

    public function testReopen()
    {
        $mock = $this->createMock(IConnection::class);
        $mock->method('Reopen')->willReturn(true);

        $this->assertTrue($mock->Reopen());
    }

    public function testClose()
    {
        $mock = $this->createMock(IConnection::class);
        $mock->expects($this->once())->method('Close');

        $mock->Close();
    }

    public function testPing()
    {
        $mock = $this->createMock(IConnection::class);
        $mock->method('Ping')->willReturn(true);

        $this->assertTrue($mock->Ping());
    }

    public function testFromConnectionInfo()
    {
        $mock = $this->getMockBuilder(IConnection::class)
                        ->disableOriginalConstructor()
                        ->getMock();
        $mock::method('FromConnectionInfo')->willReturn($mock);

        $this->assertInstanceOf(IConnection::class, $mock::FromConnectionInfo([]));
    }
}
