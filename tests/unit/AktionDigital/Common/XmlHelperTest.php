<?php
namespace PHPTDD\Colibri\Common;
use PHPTDD\BaseTestCase;
use Colibri\Common\XmlHelper;

class XmlHelperTest extends BaseTestCase {

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
     * @covers Colibri\Common\XmlHelper::Encode
     **/
    public function testXmlHelperEncode() {
        // code test functionality here
        $result1 = XmlHelper::Encode((object)['test' => 'true'], 'object');
        $result2 = XmlHelper::Encode((object)['test' => true], 'object');
        $result3 = XmlHelper::Encode((object)['test' => 5], 'object');
        $result4 = XmlHelper::Encode((object)['test' => '<html></html>'], 'object');

        $this->assertEquals('<object><test>true</test></object>', $result1);
        $this->assertEquals('<object><test>1</test></object>', $result2);
        $this->assertEquals('<object><test>5</test></object>', $result3);
        $this->assertEquals('<object><test><html></html></test></object>', $result4);

    }

    /**
     * @covers Colibri\Common\XmlHelper::Decode
     **/
    public function testXmlHelperDecode() {
        // не реализован
    }
}
