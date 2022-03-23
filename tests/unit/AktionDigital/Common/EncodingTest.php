<?php
namespace PHPTDD\Colibri\Common;
use PHPTDD\BaseTestCase;
use Colibri\Common\Encoding;

class EncodingTest extends BaseTestCase {

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
     * @covers Colibri\Common\Encoding
     **/
    public function testEncoding() {
        // code test functionality here
    }

    /**
     * @covers Colibri\Common\Encoding::Convert
     **/
    public function testEncodingConvert() {
        $result1 = Encoding::Convert('строка в кодировке UTF-8', Encoding::CP1251, Encoding::UTF8);
        $result2 = iconv('utf-8', 'windows-1251', 'строка в кодировке UTF-8');
        $this->assertEquals($result2, $result1);
    }

    /**
     * @covers Colibri\Common\Encoding::Check
     **/
    public function testEncodingCheck() {
        $this->assertTrue(Encoding::Check('строка в кодировке UTF-8', Encoding::UTF8));
        $this->assertTrue(Encoding::Check(iconv('utf-8', 'windows-1251', 'строка в кодировке UTF-8'), Encoding::CP1251));
        $this->assertFalse(Encoding::Check(iconv('utf-8', 'windows-1251', 'строка в кодировке UTF-8'), Encoding::UTF8));
    }

    /**
     * @covers Colibri\Common\Encoding::Detect
     **/
    public function testEncodingDetect() {
        $this->assertEquals(Encoding::UTF8, Encoding::Detect('строка в кодировке UTF-8'));
        $this->assertEquals(Encoding::ISO_8859_1, Encoding::Detect(iconv('utf-8', 'windows-1251', 'строка в кодировке')));
    }
}
