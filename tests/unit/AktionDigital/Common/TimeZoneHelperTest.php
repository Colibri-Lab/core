<?php
namespace PHPTDD\Colibri\Common;
use PHPTDD\BaseTestCase;
use Colibri\Common\TimeZoneHelper;

class TimeZoneHelperTest extends BaseTestCase {

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
     * @covers Colibri\Common\TimeZoneHelper::Month
     **/
    public function testTimeZoneHelperMonth() {
        
        $t = TimeZoneHelper::Month(4);
        $this->assertEquals('май', $t);

        $t = TimeZoneHelper::Month(12);
        $this->assertNull($t);

        $t = TimeZoneHelper::Month(-1);
        $this->assertNull($t);

        $t = TimeZoneHelper::Month('тест');
        $this->assertNull($t);

        $t = TimeZoneHelper::Month(null);
        $this->assertNull($t);
    }

    /**
     * @covers Colibri\Common\TimeZoneHelper::Month2
     **/
    public function testTimeZoneHelperMonth2() {

        $t = TimeZoneHelper::Month2(4);
        $this->assertEquals('мая', $t);
        
        $t = TimeZoneHelper::Month2(12);
        $this->assertNull($t);

        $t = TimeZoneHelper::Month2(-1);
        $this->assertNull($t);

        $t = TimeZoneHelper::Month2('тест');
        $this->assertNull($t);

        $t = TimeZoneHelper::Month2(null);
        $this->assertNull($t);
    }

    /**
     * @covers Colibri\Common\TimeZoneHelper::Weekday
     **/
    public function testTimeZoneHelperWeekday() {

        $t = TimeZoneHelper::Weekday(4);
        $this->assertEquals('пятница', $t);

        $t = TimeZoneHelper::Weekday(7);
        $this->assertNull($t);
        
        $t = TimeZoneHelper::Weekday(-1);
        $this->assertNull($t);

        $t = TimeZoneHelper::Weekday('тест');
        $this->assertNull($t);

        $t = TimeZoneHelper::Weekday(null);
        $this->assertNull($t);
    }

    /**
     * @covers Colibri\Common\TimeZoneHelper::FTimeU
     **/
    public function testTimeZoneHelperFTimeU() {
        
        $res = TimeZoneHelper::FTimeU('%d.%m.%Y %H:%M:%S.%f', 1616657388.891307);
        $this->assertEquals('25.03.2021 07:29:48.891300', $res);

        $res = TimeZoneHelper::FTimeU('%d.%m.%Y %H:%M:%S.%f', -1);
        $this->assertEquals('31.12.1969 23:59:59.000000', $res);

        $res = TimeZoneHelper::FTimeU('%d.%m.%Y %H:%M:%S.%f', null);
        $this->assertNull($res);

        $res = TimeZoneHelper::FTimeU('%d.%m.%Y %H:%M:%S.%f', 'asdfasdf');
        $this->assertNull($res);

    }

    /**
     * @covers Colibri\Common\TimeZoneHelper::Set
     **/
    public function testTimeZoneHelperSet() {
        $this->assertTrue(TimeZoneHelper::Set('ru'));
        $this->assertTrue(TimeZoneHelper::Set('en'));
        $this->assertFalse(TimeZoneHelper::Set('am'));
        $this->assertFalse(TimeZoneHelper::Set('us'));
    }
}
