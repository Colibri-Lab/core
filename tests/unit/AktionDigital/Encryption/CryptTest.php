<?php
namespace PHPTDD\Colibri\Encryption;
use PHPTDD\BaseTestCase;
use Colibri\Encryption\Crypt;
use Colibri\IO\Request\Encryption;

class CryptTest extends BaseTestCase {

    /**
     * This code will run before each test executes
     * @return void
     */
    protected function setUp(): void {

    }

    /**
     * This code will run after each test executes
     * @return void
     */
    protected function tearDown(): void {

    }

    /**
     * @covers Colibri\Encryption\Crypt
     **/
    public function testCrypt() {
        
        $res = Crypt::Encrypt('test', 'test');
        $this->assertEquals('xuc+Lw==', $res);

        $res = Crypt::Encrypt('test', 'test', Crypt::EncryptionAlgHex);
        $this->assertEquals('c6e73e2f', $res);

        $res = Crypt::Encrypt('test', 123123);
        $this->assertNull($res);

        $res = Crypt::Encrypt('test', null);
        $this->assertNull($res);

        $res = Crypt::Encrypt(null, 'test');
        $this->assertNull($res);

    }

    /**
     * @covers Colibri\Encryption\Crypt::Encrypt
     **/
    public function testCryptEncrypt() {
        
        $res = Crypt::Decrypt('test', 'xuc+Lw==');
        $this->assertEquals('test', $res);

        $res = Crypt::Decrypt('test', 'c6e73e2f', Crypt::EncryptionAlgHex);
        $this->assertEquals('test', $res);

        $res = Crypt::Decrypt('test', 123123);
        $this->assertNull($res);

        $res = Crypt::Decrypt('test', null);
        $this->assertNull($res);

        $res = Crypt::Decrypt(null, 'test');
        $this->assertNull($res);
    }

    /**
     * @covers Colibri\Encryption\Crypt::Decrypt
     **/
    public function testCryptDecrypt() {
        // code test functionality here
    }
}
