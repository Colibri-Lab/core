<?php

use PHPUnit\Framework\TestCase;
use Colibri\Queue\IJob;

class QueueIJobTest extends TestCase
{
    public function testIJobCanBeMocked(): void
    {
        $mock = $this->createMock(IJob::class);
        $this->assertInstanceOf(IJob::class, $mock);
    }

    public function testIJobHandleMethod(): void
    {
        $mock = $this->createMock(IJob::class);
        $mock->method('Handle')->willReturn(true);
        $logger = new \Colibri\Utils\Logs\MemoryLogger();
        $this->assertTrue($mock->Handle($logger));
    }

    public function testIJobSetHeadersMethod(): void
    {
        $mock = $this->createMock(IJob::class);
        $mock->expects($this->once())->method('SetHeaders');
        $mock->SetHeaders();
    }
}
