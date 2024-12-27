<?php

use PHPUnit\Framework\TestCase;
use Colibri\Threading\Process;
use Colibri\Threading\Worker;

class ProcessTest extends TestCase
{
    public function testCreate()
    {
        $worker = $this->getMockForAbstractClass(Worker::class);
        $process = Process::Create($worker, true, '/entry');
        $this->assertInstanceOf(Process::class, $process);
    }

    public function testRun()
    {
        $worker = $this->getMockForAbstractClass(Worker::class);
        $process = new Process($worker, true, '/entry');
        $process->Run((object)['param' => 'value']);
        $this->assertNotEmpty($process->__get('pid'));
    }

    public function testIsRunning()
    {
        $worker = $this->getMockForAbstractClass(Worker::class);
        $process = new Process($worker, true, '/entry');
        $process->Run((object)['param' => 'value']);
        $this->assertTrue($process->IsRunning());
    }

    public function testStop()
    {
        $worker = $this->getMockForAbstractClass(Worker::class);
        $process = new Process($worker, true, '/entry');
        $process->Run((object)['param' => 'value']);
        $this->assertTrue($process->Stop());
    }
}
