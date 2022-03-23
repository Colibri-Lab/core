<?php
namespace PHPTDD\Colibri\Common;
use PHPTDD\BaseTestCase;
use Colibri\Common\NumericHelper;

class NumericHelperTest extends BaseTestCase {

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
     * @covers Colibri\Common\NumericHelper::ToMoney
     **/
    public function testNumericHelperToMoney() {
        // code test functionality here

        $formated1 = '1&nbsp;000.01';
        $formated2 = NumericHelper::ToMoney(1000.01);
        $this->assertEquals($formated1, $formated2);
    }

    /**
     * @covers Colibri\Common\NumericHelper::Format
     **/
    public function testNumericHelperFormat() {
        // code test functionality here
        $formated1 = '1&nbsp;000,01';
        $formated2 = NumericHelper::Format(1000.01, ',', 2, false, '&nbsp;');
        $this->assertEquals($formated1, $formated2);

        $formated1 = '1 000';
        $formated2 = NumericHelper::Format(1000, ',', 2, true, ' ');
        $this->assertEquals($formated1, $formated2);

        $formated1 = '1 000,00';
        $formated2 = NumericHelper::Format(1000, ',', 2, false, ' ');
        $this->assertEquals($formated1, $formated2);
    }

}
