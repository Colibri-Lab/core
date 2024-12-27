<?php

namespace Colibri\Tests\Encryption;

use Colibri\Encryption\Crypt;
use PHPUnit\Framework\TestCase;

class CryptTest extends TestCase
{
    public function testEncryptBase64()
    {
        $key = 'testkey';
        $data = 'testdata';
        $encrypted = Crypt::Encrypt($key, $data, Crypt::EncryptionAlgBase64);
        $this->assertNotEquals($data, $encrypted);
        $this->assertEquals($data, Crypt::Decrypt($key, $encrypted, Crypt::EncryptionAlgBase64));
    }

    public function testEncryptHex()
    {
        $key = 'testkey';
        $data = 'testdata';
        $encrypted = Crypt::Encrypt($key, $data, Crypt::EncryptionAlgHex);
        $this->assertNotEquals($data, $encrypted);
        $this->assertEquals($data, Crypt::Decrypt($key, $encrypted, Crypt::EncryptionAlgHex));
    }
}
