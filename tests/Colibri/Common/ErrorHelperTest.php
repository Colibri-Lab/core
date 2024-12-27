<?php

use PHPUnit\Framework\TestCase;
use Colibri\Common\ErrorHelper;

class ErrorHelperTest extends TestCase
{
    public function testTelegram()
    {
        $this->expectException(\Exception::class);
        ErrorHelper::Telegram('channel', 'message');
    }
}
