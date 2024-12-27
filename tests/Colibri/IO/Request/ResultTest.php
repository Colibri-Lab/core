<?php

use PHPUnit\Framework\TestCase;
use Colibri\IO\Request\Result;

class ResultTest extends TestCase
{
    public function testProperties()
    {
        $result = new Result();
        $result->status = 200;
        $result->data = 'response data';
        $result->error = 'error message';
        $result->headers = ['Content-Type' => 'application/json'];
        $result->httpheaders = ['Content-Type' => 'application/json'];

        $this->assertEquals(200, $result->status);
        $this->assertEquals('response data', $result->data);
        $this->assertEquals('error message', $result->error);
        $this->assertEquals(['Content-Type' => 'application/json'], $result->headers);
        $this->assertEquals(['Content-Type' => 'application/json'], $result->httpheaders);
    }
}
