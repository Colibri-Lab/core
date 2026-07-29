<?php

use PHPUnit\Framework\TestCase;
use Colibri\Common\DateHelper;

class DateHelperTest extends TestCase
{
    public function testCreate(): void
    {
        $timestamp = DateHelper::Create(2024, 1, 1);
        $this->assertIsInt($timestamp);
        $this->assertEquals('2024-01-01', date('Y-m-d', $timestamp));
    }

    public function testLastDayOfMonth(): void
    {
        $date = DateHelper::Create(2024, 1, 1);
        $lastDay = DateHelper::LastDayOfMonth($date);
        $this->assertEquals(31, (int) date('d', $lastDay));
    }

    public function testLastDayOfMonthFebruary(): void
    {
        $date = DateHelper::Create(2024, 2, 1);
        $lastDay = DateHelper::LastDayOfMonth($date);
        $this->assertEquals(29, (int) date('d', $lastDay)); // 2024 is a leap year
    }

    public function testRFC(): void
    {
        $rfc = DateHelper::RFC(mktime(0, 0, 0, 1, 1, 2024));
        $this->assertIsString($rfc);
        $this->assertStringContainsString('2024', $rfc);
    }

    public function testToDbString(): void
    {
        $ts = mktime(12, 30, 0, 6, 15, 2023);
        $dbString = DateHelper::ToDbString($ts);
        $this->assertEquals('2023-06-15 12:30:00', $dbString);
    }

    public function testToDbStringNull(): void
    {
        $result = DateHelper::ToDbString(null);
        $this->assertIsString($result);
    }

    public function testToUnixTime(): void
    {
        $ts = DateHelper::ToUnixTime('2024-01-01 00:00:00');
        $this->assertIsInt($ts);
        $this->assertEquals('2024-01-01', date('Y-m-d', $ts));
    }

    public function testToUnixTimeInvalidReturnsNull(): void
    {
        $result = DateHelper::ToUnixTime('not-a-date');
        $this->assertNull($result);
    }

    public function testDiff(): void
    {
        $time1 = mktime(0, 0, 0, 1, 1, 2024);
        $time2 = mktime(0, 0, 0, 1, 10, 2024);
        $diff = DateHelper::Diff($time1, $time2);
        $this->assertIsObject($diff);
        $this->assertEquals(9, $diff->days);
    }

    public function testFromDDMMYYYY(): void
    {
        $result = DateHelper::FromDDMMYYYY('01.06.2023');
        $this->assertStringContainsString('2023-06-01', $result);
    }

    public function testToISODate(): void
    {
        $result = DateHelper::ToISODate('2024-01-15');
        $this->assertIsString($result);
        $this->assertStringContainsString('2024', $result);
    }

    public function testMc(): void
    {
        $result = DateHelper::Mc();
        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    public function testNc(): void
    {
        $result = DateHelper::Nc();
        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }

    public function testYEARConstant(): void
    {
        $this->assertEquals(31556926, DateHelper::YEAR);
    }

    public function testMONTHConstant(): void
    {
        $this->assertEquals(2629744, DateHelper::MONTH);
    }

    public function testWEEKConstant(): void
    {
        $this->assertEquals(604800, DateHelper::WEEK);
    }

    public function testDAYConstant(): void
    {
        $this->assertEquals(86400, DateHelper::DAY);
    }

    public function testHOURConstant(): void
    {
        $this->assertEquals(3600, DateHelper::HOUR);
    }

    public function testMINUTEConstant(): void
    {
        $this->assertEquals(60, DateHelper::MINUTE);
    }

    public function testDaysInMonth(): void
    {
        $dt = new \DateTime('2024-01-15');
        $result = DateHelper::DaysInMonth($dt);
        $this->assertEquals(31, $result);
    }

    public function testTimeToMinute(): void
    {
        $result = DateHelper::TimeToMinute('02:30');
        $this->assertEquals(150, $result);
    }

    public function testAgeYears(): void
    {
        $birthYear = date('Y') - 25;
        $ts = mktime(0, 0, 0, 1, 1, $birthYear);
        $age = DateHelper::AgeYears($ts);
        $this->assertIsInt($age);
        $this->assertGreaterThanOrEqual(24, $age);
    }
}
