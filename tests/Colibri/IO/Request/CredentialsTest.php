<?php

use PHPUnit\Framework\TestCase;
use Colibri\IO\Request\Credentials;

class CredentialsTest extends TestCase
{
    public function testConstructor()
    {
        $credentials = new Credentials('login', 'password', true);
        $this->assertEquals('login', $credentials->login);
        $this->assertEquals('password', $credentials->secret);
        $this->assertTrue($credentials->ssl);
    }
}
