<?php

use PHPUnit\Framework\TestCase;
use Colibri\Common\SmtpHelper;

class SmtpHelperTest extends TestCase
{
    public function testSend()
    {
        $config = [
            'enabled' => false,
            'host' => 'smtp.example.com',
            'port' => 587,
            'secure' => 'tls',
            'user' => 'user@example.com',
            'password' => 'password',
            'from' => 'from@example.com',
            'fromname' => 'Example'
        ];

        $this->expectNotToPerformAssertions();
        SmtpHelper::Send($config, 'to@example.com', 'Test Subject', 'Test Body');
    }
}
