<?php

use PHPUnit\Framework\TestCase;
use Colibri\Exceptions\JobAllreadyRunningException;

class JobAllreadyRunningExceptionTest extends TestCase
{
    public function testIsException(): void
    {
        $e = new JobAllreadyRunningException('job running');
        $this->assertInstanceOf(\Exception::class, $e);
        $this->assertInstanceOf(JobAllreadyRunningException::class, $e);
    }

    public function testMessage(): void
    {
        $e = new JobAllreadyRunningException('already running');
        $this->assertEquals('already running', $e->getMessage());
    }

    public function testThrowAndCatch(): void
    {
        $this->expectException(JobAllreadyRunningException::class);
        throw new JobAllreadyRunningException('test');
    }

    public function testCodeConstant(): void
    {
        $this->assertEquals(500, JobAllreadyRunningException::Code);
    }

    public function testMessageConstant(): void
    {
        $this->assertEquals('Job is allready running', JobAllreadyRunningException::Message);
    }
}
