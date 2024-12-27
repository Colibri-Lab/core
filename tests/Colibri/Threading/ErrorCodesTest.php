<?php

use PHPUnit\Framework\TestCase;
use Colibri\Threading\ErrorCodes;

class ErrorCodesTest extends TestCase
{
    public function testToString()
    {
        $this->assertEquals('Unknown property', ErrorCodes::ToString(ErrorCodes::UnknownProperty));
        $this->assertNull(ErrorCodes::ToString(999));
    }
}
