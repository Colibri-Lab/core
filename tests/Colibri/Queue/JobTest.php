<?php

use PHPUnit\Framework\TestCase;
use Colibri\Queue\Job;
use Colibri\Utils\ExtendedObject;
use Colibri\Utils\Logs\Logger;

class QueueJobTest extends TestCase
{
    private Job $job;

    protected function setUp(): void
    {
        $this->job = new class extends Job {
            public function Handle(Logger $logger): bool
            {
                return true;
            }
        };
    }

    public function testCreateMethod(): void
    {
        $payload = new ExtendedObject(['key' => 'value']);
        $concreteJob = new class extends Job {
            public function Handle(Logger $logger): bool { return true; }
        };
        $job = $concreteJob::Create($payload, 'default', 0);
        $this->assertInstanceOf(Job::class, $job);
    }

    public function testCreateSetsPayload(): void
    {
        $payload = new ExtendedObject(['data' => 'test']);
        $job = (new class extends Job {
            public function Handle(Logger $logger): bool { return true; }
        })::Create($payload, 'test_queue');
        $this->assertSame($payload, $job->payload);
    }

    public function testCreateSetsQueue(): void
    {
        $payload = new ExtendedObject([]);
        $job = (new class extends Job {
            public function Handle(Logger $logger): bool { return true; }
        })::Create($payload, 'my_queue');
        $this->assertEquals('my_queue', $job->queue);
    }

    public function testCreateSetsAttempts(): void
    {
        $payload = new ExtendedObject([]);
        $job = (new class extends Job {
            public function Handle(Logger $logger): bool { return true; }
        })::Create($payload, 'default', 3);
        $this->assertEquals(3, $job->attempts);
    }

    public function testIsLastAttempt(): void
    {
        $payload = new ExtendedObject([]);
        $job = (new class extends Job {
            public function Handle(Logger $logger): bool { return true; }
        })::Create($payload, 'default', 5);
        $this->assertTrue($job->IsLastAttempt());
    }

    public function testIsNotLastAttempt(): void
    {
        $payload = new ExtendedObject([]);
        $job = (new class extends Job {
            public function Handle(Logger $logger): bool { return true; }
        })::Create($payload, 'default', 2);
        $this->assertFalse($job->IsLastAttempt());
    }

    public function testIsParallelDefault(): void
    {
        $payload = new ExtendedObject([]);
        $job = (new class extends Job {
            public function Handle(Logger $logger): bool { return true; }
        })::Create($payload);
        $this->assertFalse($job->IsParallel());
    }

    public function testIsParallelTrue(): void
    {
        $payload = new ExtendedObject([]);
        $job = (new class extends Job {
            public function Handle(Logger $logger): bool { return true; }
        })::Create($payload, 'default', 0, true);
        $this->assertTrue($job->IsParallel());
    }

    public function testHandleReturnsTrue(): void
    {
        $logger = new \Colibri\Utils\Logs\MemoryLogger();
        $this->assertTrue($this->job->Handle($logger));
    }

    public function testKeyReturnsString(): void
    {
        $key = $this->job->Key();
        $this->assertIsString($key);
    }
}
