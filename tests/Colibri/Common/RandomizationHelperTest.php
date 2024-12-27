<?php

use PHPUnit\Framework\TestCase;
use Colibri\Common\RandomizationHelper;

class RandomizationHelperTest extends TestCase
{
    public function testSeed()
    {
        $this->assertIsInt(RandomizationHelper::Seed());
    }

    public function testInteger()
    {
        $randomInt = RandomizationHelper::Integer(1, 10);
        $this->assertGreaterThanOrEqual(1, $randomInt);
        $this->assertLessThanOrEqual(10, $randomInt);
    }

    public function testMixed()
    {
        $randomString = RandomizationHelper::Mixed(10);
        $this->assertEquals(10, strlen($randomString));
    }

    public function testNumeric()
    {
        $randomNumeric = RandomizationHelper::Numeric(10);
        $this->assertEquals(10, strlen($randomNumeric));
        $this->assertMatchesRegularExpression('/^[0-9]+$/', $randomNumeric);
    }

    public function testCharacter()
    {
        $randomCharacter = RandomizationHelper::Character(10);
        $this->assertEquals(10, strlen($randomCharacter));
        $this->assertMatchesRegularExpression('/^[A-Za-z]+$/', $randomCharacter);
    }
}
