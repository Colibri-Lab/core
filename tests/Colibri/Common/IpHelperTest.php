<?php

use PHPUnit\Framework\TestCase;
use Colibri\Common\IpHelper;

class IpHelperTest extends TestCase
{
    public function testMatchExactIp(): void
    {
        $this->assertTrue(IpHelper::CheckIfInPattern('192.168.1.1', '192.168.1.1'));
    }

    public function testMatchWithWildcard(): void
    {
        $this->assertTrue(IpHelper::CheckIfInPattern('192.168.*.*', '192.168.5.100'));
    }

    public function testNoMatchWithWildcard(): void
    {
        $this->assertFalse(IpHelper::CheckIfInPattern('192.168.*.*', '10.0.0.1'));
    }

    public function testMatchWithMultiplePatterns(): void
    {
        $this->assertTrue(IpHelper::CheckIfInPattern('10.*.*.*;192.168.*.*', '192.168.1.1'));
    }

    public function testNoMatchWithMultiplePatterns(): void
    {
        $this->assertFalse(IpHelper::CheckIfInPattern('10.*.*.*;172.16.*.*', '192.168.1.1'));
    }

    public function testMatchArrayPatterns(): void
    {
        $patterns = ['192.168.1.*', '10.0.0.*'];
        $this->assertTrue(IpHelper::CheckIfInPattern($patterns, '192.168.1.50'));
    }

    public function testNoMatchArrayPatterns(): void
    {
        $patterns = ['192.168.1.*', '10.0.0.*'];
        $this->assertFalse(IpHelper::CheckIfInPattern($patterns, '172.16.0.1'));
    }

    public function testFullWildcard(): void
    {
        $this->assertTrue(IpHelper::CheckIfInPattern('*.*.*.*', '1.2.3.4'));
    }
}
