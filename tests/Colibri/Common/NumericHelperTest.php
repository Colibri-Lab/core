<?php

use PHPUnit\Framework\TestCase;
use Colibri\Common\NumericHelper;

class NumericHelperTest extends TestCase
{
    public function testToMoney()
    {
        $this->assertEquals('1.00', NumericHelper::ToMoney(1));
        $this->assertEquals('1.50', NumericHelper::ToMoney(1.5));
    }

    public function testFormat()
    {
        $this->assertEquals('1.00', NumericHelper::Format(1));
        $this->assertEquals('1,000.00', NumericHelper::Format(1000, '.', 2, false, ','));
    }

    public function testNormalize()
    {
        $this->assertEquals(1.5, NumericHelper::Normalize('1,5'));
        $this->assertEquals(1000.5, NumericHelper::Normalize('1 000,5'));
    }
}
