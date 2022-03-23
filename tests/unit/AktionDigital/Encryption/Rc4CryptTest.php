<?php
namespace PHPTDD\Colibri\Encryption;
use PHPTDD\BaseTestCase;
use Colibri\Encryption\Rc4Crypt;

class Rc4CryptTest extends BaseTestCase {

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
     * @covers Colibri\Encryption\Rc4Crypt::Encrypt
     **/
    public function testRc4CryptEncrypt() {
        $res1 = Rc4Crypt::Encrypt('test', 'test');
        $res2 = Rc4Crypt::Decrypt('test', $res1);
        $this->assertEquals('test', $res2);
    }

    /**
     * @covers Colibri\Encryption\Rc4Crypt::Decrypt
     **/
    public function testRc4CryptDecrypt() {
        $this->testRc4CryptEncrypt();
    }
}
