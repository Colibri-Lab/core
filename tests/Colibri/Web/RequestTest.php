<?php

use PHPUnit\Framework\TestCase;
use Colibri\Web\Request;

class RequestTest extends TestCase
{
    public function testCreate()
    {
        $request = Request::Create();
        $this->assertInstanceOf(Request::class, $request);
    }

    public function testUri()
    {
        $request = Request::Create();
        $uri = $request->Uri(['param' => 'value'], ['remove']);
        $this->assertStringContainsString('param=value', $uri);
    }

    public function testGetPayloadCopy()
    {
        $request = Request::Create();
        $payload = $request->GetPayloadCopy(Request::PAYLOAD_TYPE_JSON);
        $this->assertInstanceOf(PayloadCopy::class, $payload);
    }
}
