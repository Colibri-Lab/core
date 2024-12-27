<?php

use PHPUnit\Framework\TestCase;
use Colibri\IO\Request\Request;
use Colibri\IO\Request\Type;
use Colibri\IO\Request\Encryption;
use Colibri\IO\Request\Data;

class RequestTest extends TestCase
{
    public function testConstructor()
    {
        $request = new Request('http://example.com', Type::Post, Encryption::JsonEncoded, new Data(), 'boundary');
        $this->assertEquals('http://example.com', $request->target);
        $this->assertEquals(Type::Post, $request->method);
        $this->assertEquals(Encryption::JsonEncoded, $request->encryption);
        $this->assertInstanceOf(Data::class, $request->postData);
        $this->assertEquals('boundary', $request->boundary);
    }

    public function testGet()
    {
        $result = Request::Get('http://example.com');
        $this->assertEquals(200, $result->status);
    }
}
