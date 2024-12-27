<?php

use PHPUnit\Framework\TestCase;
use Colibri\IO\Request\Type;

class TypeTest extends TestCase
{
    public function testConstants()
    {
        $this->assertEquals('post', Type::Post);
        $this->assertEquals('get', Type::Get);
        $this->assertEquals('head', Type::Head);
        $this->assertEquals('delete', Type::Delete);
        $this->assertEquals('put', Type::Put);
        $this->assertEquals('patch', Type::Patch);
    }
}
