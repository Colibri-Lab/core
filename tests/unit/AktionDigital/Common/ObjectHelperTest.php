<?php
namespace PHPTDD\Colibri\Common;
use PHPTDD\BaseTestCase;
use Colibri\Common\ObjectHelper;

class ObjectHelperTest extends BaseTestCase {

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
     * @covers Colibri\Common\ObjectHelper::ArrayToObject
     **/
    public function testObjectHelperArrayToObject() {
        // code test functionality here
        $result = ObjectHelper::ArrayToObject(['test' => 1]);
        $this->assertTrue(is_object($result));
        $this->assertEquals('{"test":1}', json_encode($result));

        $result = ObjectHelper::ArrayToObject((object)['test' => 1]);
        $this->assertTrue(is_object($result));
        $this->assertEquals('{"test":1}', json_encode($result));

        $this->assertNull(ObjectHelper::ArrayToObject('adfasdf'));
        $this->assertNull(ObjectHelper::ArrayToObject(1));
    }
}
