<?php

namespace Colibri\Tests\Encryption;

use Colibri\Encryption\Rc4Crypt;
use PHPUnit\Framework\TestCase;

class Rc4CryptTest extends TestCase
{
    public function testEncrypt()
    {
        $key = 'testkey';
        $data = 'testdata';
        $encrypted = Rc4Crypt::Encrypt($key, $data);
        $this->assertNotEquals($data, $encrypted);
    }

    public function testDecrypt()
    {
        $key = 'testkey';
        $data = 'testdata';
        $encrypted = Rc4Crypt::Encrypt($key, $data);
        $decrypted = Rc4Crypt::Decrypt($key, $encrypted);
        $this->assertEquals($data, $decrypted);
    }
}
