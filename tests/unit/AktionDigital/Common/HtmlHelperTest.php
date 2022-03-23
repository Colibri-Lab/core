<?php
namespace PHPTDD\Colibri\Common;
use PHPTDD\BaseTestCase;
use Colibri\Common\HtmlHelper;

class HtmlHelperTest extends BaseTestCase {

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
     * @covers Colibri\Common\HtmlHelper::Encode
     **/
    public function testHtmlHelperEncode() {
        
        $result1 = HtmlHelper::Encode((object)['test' => 'true'], 'object');
        $result2 = HtmlHelper::Encode((object)['test' => true], 'object');
        $result3 = HtmlHelper::Encode((object)['test' => 5], 'object');
        $result4 = HtmlHelper::Encode((object)['test' => '<html></html>'], 'object');

        $this->assertEquals('<div class="object"><div class="test">true</div></div>', $result1);
        $this->assertEquals('<div class="object"><div class="test">1</div></div>', $result2);
        $this->assertEquals('<div class="object"><div class="test">5</div></div>', $result3);
        $this->assertEquals('<div class="object"><div class="test"><html></html></div></div>', $result4);

    }

    /**
     * @covers Colibri\Common\HtmlHelper::Decode
     **/
    public function testHtmlHelperDecode() {
        // метод не реализован
    }
}
