<?php

use PHPUnit\Framework\TestCase;
use Colibri\Common\TimeZoneHelper;

class TimeZoneHelperTest extends TestCase
{
    public function testGetTimeZoneList()
    {
        $timeZones = TimeZoneHelper::GetTimeZoneList();
        $this->assertIsArray($timeZones);
        $this->assertNotEmpty($timeZones);
    }

    public function testGetTimeZoneOffset()
    {
        $offset = TimeZoneHelper::GetTimeZoneOffset('UTC');
        $this->assertEquals(0, $offset);
    }
}
