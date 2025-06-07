<?php

use PHPUnit\Framework\TestCase;
use Colibri\Threading\Manager;
use Colibri\Threading\Worker;
use Colibri\Threading\Process;

class ManagerTest extends TestCase
{
    public function testCreate()
    {
        $manager = Manager::Instance();
        $this->assertInstanceOf(Manager::class, $manager);
    }

    public function testCreateProcess()
    {
        $manager = Manager::Instance();
        $worker = $this->getMockForAbstractClass(Worker::class);
        $process = $manager->CreateProcess($worker, true);
        $this->assertInstanceOf(Process::class, $process);
    }
}
