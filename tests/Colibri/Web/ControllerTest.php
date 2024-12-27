<?php

use PHPUnit\Framework\TestCase;
use Colibri\Web\Controller;
use Colibri\Web\RequestCollection;
use Colibri\Web\PayloadCopy;

class ControllerTest extends TestCase
{
    public function testFinish()
    {
        $controller = new Controller();
        $result = $controller->Finish(200, 'OK', ['data' => 'value']);
        $this->assertEquals(200, $result->code);
        $this->assertEquals('OK', $result->message);
        $this->assertEquals(['data' => 'value'], $result->result);
    }

    public function testGetEntryPoint()
    {
        $url = Controller::GetEntryPoint('method', 'json', ['param' => 'value']);
        $this->assertStringContainsString('method.json', $url);
        $this->assertStringContainsString('param=value', $url);
    }

    public function testInvoke()
    {
        $controller = $this->getMockBuilder(Controller::class)
            ->onlyMethods(['someMethod'])
            ->getMock();

        $controller->method('someMethod')->willReturn((object)['result' => 'value']);

        $get = new RequestCollection([]);
        $post = new RequestCollection([]);
        $payload = new PayloadCopy('json');

        $result = $controller->Invoke('someMethod', $get, $post, $payload);
        $this->assertEquals('value', $result->result);
    }
}
