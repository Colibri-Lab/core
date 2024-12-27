<?php

use PHPUnit\Framework\TestCase;
use Colibri\IO\Request\Encryption;

class EncryptionTest extends TestCase
{
    public function testConstants()
    {
        $this->assertEquals('multipart/form-data', Encryption::Multipart);
        $this->assertEquals('application/x-www-form-urlencoded', Encryption::UrlEncoded);
        $this->assertEquals('application/x-www-form-xmlencoded', Encryption::XmlEncoded);
        $this->assertEquals('application/json', Encryption::JsonEncoded);
    }
}
