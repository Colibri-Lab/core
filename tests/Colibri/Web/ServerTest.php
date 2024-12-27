<?php

use PHPUnit\Framework\TestCase;
use Colibri\Web\Server;
use Colibri\App;

class ServerTest extends TestCase
{
    public function testFinish()
    {
        $server = new Server();
        $this->expectOutputString('{"result":[]}', 'Expected JSON output');
        $server->Finish(Server::JSON, (object)['result' => []]);
    }

    public function testRun()
    {
        $server = $this->getMockBuilder(Server::class)
            ->onlyMethods(['__parseCommand', 'Finish'])
            ->getMock();

        $server->method('__parseCommand')->willReturn([Server::JSON, 'SomeClass', 'someMethod', true]);
        $server->expects($this->once())->method('Finish');

        App::$request = (object)[
            'server' => (object)['request_method' => 'GET'],
            'get' => (object)[],
            'post' => (object)[],
            'payload' => (object)[]
        ];

        $server->Run('/some/command');
    }
}
