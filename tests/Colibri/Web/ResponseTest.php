<?php

use PHPUnit\Framework\TestCase;
use Colibri\Web\Response;

class ResponseTest extends TestCase
{
    public function testCreate()
    {
        $response = Response::Create();
        $this->assertInstanceOf(Response::class, $response);
    }

    public function testNoCache()
    {
        $response = Response::Create();
        $response->NoCache();
        $this->expectOutputString('X-Accel-Expires: 0');
    }

    public function testContentType()
    {
        $response = Response::Create();
        $response->ContentType('application/json');
        $this->expectOutputString('Content-type: application/json');
    }
}
