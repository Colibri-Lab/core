<?php

use PHPUnit\Framework\TestCase;
use Colibri\Threading\Worker;
use Colibri\Utils\Logs\Logger;

class WorkerTest extends TestCase
{
    public function testConstructor()
    {
        $worker = $this->getMockForAbstractClass(Worker::class, [60, 5, 'test_key']);
        $this->assertEquals(60, $worker->__get('timelimit'));
        $this->assertEquals(5, $worker->__get('prio'));
        $this->assertEquals('test_key', $worker->__get('key'));
    }

    public function testPrepareParams()
    {
        $worker = $this->getMockForAbstractClass(Worker::class);
        $params = ['param1' => 'value1'];
        $serializedParams = $worker->PrepareParams($params);
        $this->assertEquals(serialize($params), $serializedParams);
    }

    public function testPrepare()
    {
        $worker = $this->getMockForAbstractClass(Worker::class);
        $params = ['param1' => 'value1'];
        $worker->Prepare(serialize($params));
        $this->assertEquals($params, $worker->_params);
    }

    public function testSerialize()
    {
        $worker = $this->getMockForAbstractClass(Worker::class);
        $serializedWorker = $worker->Serialize();
        $this->assertEquals(serialize($worker), $serializedWorker);
    }

    public function testUnserialize()
    {
        $worker = $this->getMockForAbstractClass(Worker::class);
        $serializedWorker = serialize($worker);
        $unserializedWorker = Worker::Unserialize($serializedWorker);
        $this->assertEquals($worker, $unserializedWorker);
    }
}
