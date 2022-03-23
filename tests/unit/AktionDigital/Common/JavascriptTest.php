<?php
namespace PHPTDD\Colibri\Common;
use PHPTDD\BaseTestCase;
use Colibri\Common\Javascript;

class JavascriptTest extends BaseTestCase {

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
     * @covers Colibri\Common\Javascript::Shrink
     **/
    public function testJavascriptShrink() {

        // необходимо дополнить проверками
        $result1 = Javascript::Shrink('function(a, b, c, d) {
            // test comment
            a = 3;
            b = 4;
            c = 5;
            d = 6;
        }');

        $this->assertEquals('function(a,b,c,d){a=3;b=4;c=5;d=6;}', $result1);

    }
}
