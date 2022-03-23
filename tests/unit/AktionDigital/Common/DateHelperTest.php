<?php
namespace PHPTDD\Colibri\Common;
use PHPTDD\BaseTestCase;
use Colibri\Common\DateHelper;

class DateHelperTest extends BaseTestCase {

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
     * @covers Colibri\Common\DateHelper
     **/
    public function testDateHelper() {
        // code test functionality here
    }

    /**
     * @covers Colibri\Common\DateHelper::ToDbString
     **/
    public function testDateHelperToDbString() {
        $this->assertEquals(strftime('%Y-%m-%d %H:%M:%S', time()), DateHelper::ToDbString(time()));
        
        $format = '%Y-%m-%d';
        $this->assertEquals(strftime($format, time()), DateHelper::ToDbString(time(), $format));

        $format = '%H:%M:%S';
        $this->assertEquals(strftime($format, time()), DateHelper::ToDbString(time(), $format));

        $this->assertEquals(strftime('%Y-%m-%d %H:%M:%S', time()), DateHelper::ToDbString(strftime('%Y-%m-%d %H:%M:%S', time())));

    }

    /**
     * @covers Colibri\Common\DateHelper::RFC
     **/
    public function testDateHelperRFC() {

        // непонятно как тестировать

        $tz = date('Z');
        $tzs = ($tz < 0) ? '-' : '+';
        $tz = abs($tz);
        $tz = (int)($tz/3600)*100 + ($tz%3600)/60;
        $compare = sprintf("%s %s%04d", date('D, j M Y H:i:s', time()), $tzs, $tz);

        $this->assertEquals($compare, DateHelper::RFC(time()));
    }

    /**
     * @covers Colibri\Common\DateHelper::ToHumanDate
     **/
    public function testDateHelperToHumanDate() {
        // code test functionality here
        $this->assertEquals('22 марта 2021 18:31', DateHelper::ToHumanDate(1616437870, true));
        $this->assertEquals('1 января 1970 00:00', DateHelper::ToHumanDate(0, true));
    }

    /**
     * @covers Colibri\Common\DateHelper::ToUnixTime
     **/
    public function testDateHelperToUnixTime() {
        $this->assertEquals(1577836800, DateHelper::ToUnixTime('2020-01-01'));
        $this->assertNull(DateHelper::ToUnixTime(123123));
        $this->assertNull(DateHelper::ToUnixTime(null));
    }

    /**
     * @covers Colibri\Common\DateHelper::Age
     **/
    public function testDateHelperAge() {
        $this->assertEquals('год назад', DateHelper::Age(strtotime('-1 year', time())));
        $this->assertEquals('4 недели назад', DateHelper::Age(strtotime('-1 month', time())));
        $this->assertEquals('5 месяцев назад', DateHelper::Age(strtotime('-5 month', time())));
    }

    /**
     * @covers Colibri\Common\DateHelper::AgeYears
     **/
    public function testDateHelperAgeYears() {
        $this->assertEquals(1, DateHelper::AgeYears(strtotime('-1 year', time())));
        $this->assertEquals(0, DateHelper::AgeYears(strtotime('-1 month', time())));
        $this->assertEquals(10, DateHelper::AgeYears(strtotime('-10 year', time())));
        $this->assertEquals(1, DateHelper::AgeYears(strtotime('-12 month', time())));
        $this->assertEquals(1, DateHelper::AgeYears(strtotime('-15 month', time())));
        $this->assertFalse(DateHelper::AgeYears('adsfasdf'));
    }

    /**
     * @covers Colibri\Common\DateHelper::TimeToString
     **/
    public function testDateHelperTimeToString() {
        $this->assertEquals('30:38:59', DateHelper::TimeToString(1616438339));
        $this->assertFalse(DateHelper::TimeToString(-1));
        $this->assertFalse(DateHelper::TimeToString('asldkjhfalksjdfhalkjshf'));
    }

    /**
     * @covers Colibri\Common\DateHelper::Diff
     **/
    public function testDateHelperDiff() {
        $this->assertEquals(json_encode(['years' => 0, 'months' => 0, 'days' => 5]), json_encode(DateHelper::Diff(time(), time() + 5 * 86400)));
        $this->assertEquals(json_encode(['years' => 1, 'months' => 0, 'days' => 0]), json_encode(DateHelper::Diff(time(), time() + 365 * 86400)));
        $this->assertEquals(json_encode(['years' => 1, 'months' => 0, 'days' => 28]), json_encode(DateHelper::Diff(time(), time() + 365 * 86400 + 28 * 86400)));
        $this->assertEquals(json_encode(['years' => 1, 'months' => 1, 'days' => 9]), json_encode(DateHelper::Diff(time(), time() + 365 * 86400 + 40 * 86400)));
    }
}
