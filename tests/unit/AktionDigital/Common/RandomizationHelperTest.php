<?php
namespace PHPTDD\Colibri\Common;
use PHPTDD\BaseTestCase;
use Colibri\Common\RandomizationHelper;

class RandomizationHelperTest extends BaseTestCase {

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
     * @covers Colibri\Common\RandomizationHelper::Seed
     **/
    public function testRandomizationHelperSeed() {
        // code test functionality here
        $res = RandomizationHelper::Seed();
        $this->assertTrue(is_numeric($res));
        $this->assertEquals('double', gettype($res));
    }

    /**
     * @covers Colibri\Common\RandomizationHelper::Integer
     **/
    public function testRandomizationHelperInteger() {
        // code test functionality here
        for($i=0; $i<100; $i++) {
            $res = RandomizationHelper::Integer(10, 20);
            $this->assertGreaterThanOrEqual(10, $res);
            $this->assertLessThanOrEqual(20, $res);
        }
    }

    /**
     * @covers Colibri\Common\RandomizationHelper::Mixed
     **/
    public function testRandomizationHelperMixed() {
        // code test functionality here
        $res = RandomizationHelper::Mixed(20);
        $this->assertTrue(is_string($res));
        $this->assertEquals(20, strlen($res));
    }

    /**
     * @covers Colibri\Common\RandomizationHelper::Numeric
     **/
    public function testRandomizationHelperNumeric() {
        // code test functionality here
        $res = RandomizationHelper::Numeric(20);
        $this->assertTrue(is_string($res));
        $this->assertTrue(is_numeric($res));
        $this->assertEquals(20, strlen($res));
    }

    /**
     * @covers Colibri\Common\RandomizationHelper::Character
     **/
    public function testRandomizationHelperCharacter() {
        // code test functionality here
        $res = RandomizationHelper::Character(20);
        $this->assertTrue(is_string($res));
        $this->assertEquals(20, strlen($res));
    }
}
