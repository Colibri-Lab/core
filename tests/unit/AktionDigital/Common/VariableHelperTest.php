<?php
namespace PHPTDD\Colibri\Common;
use PHPTDD\BaseTestCase;
use Colibri\Common\VariableHelper;

class VariableHelperTest extends BaseTestCase {

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
     * @covers Colibri\Common\VariableHelper::IsEmpty
     **/
    public function testVariableHelperIsEmpty() {
        // code test functionality here
        $this->assertTrue(VariableHelper::IsEmpty(null));
        $this->assertFalse(VariableHelper::IsEmpty(0));
        $this->assertFalse(VariableHelper::IsEmpty('null'));
        $this->assertTrue(VariableHelper::IsEmpty(''));
    }

    /**
     * @covers Colibri\Common\VariableHelper::IsNull
     **/
    public function testVariableHelperIsNull() {
        // code test functionality here
        $this->assertTrue(VariableHelper::IsNull(null));
        $this->assertFalse(VariableHelper::IsNull(0));
        $this->assertFalse(VariableHelper::IsNull('null'));
    }

    /**
     * @covers Colibri\Common\VariableHelper::IsObject
     **/
    public function testVariableHelperIsObject() {
        // code test functionality here
        $this->assertFalse(VariableHelper::IsObject(true));
        $this->assertFalse(VariableHelper::IsObject(false));
        $this->assertFalse(VariableHelper::IsObject(1));
        $this->assertFalse(VariableHelper::IsObject(0));
        $this->assertFalse(VariableHelper::IsObject(['asdfasdf']));
        $this->assertFalse(VariableHelper::IsObject('true'));
        $this->assertFalse(VariableHelper::IsObject(['asdfasdf' => 1]));
        $this->assertTrue(VariableHelper::IsObject((object)['asdfasdf' => 1]));
    }

    /**
     * @covers Colibri\Common\VariableHelper::IsArray
     **/
    public function testVariableHelperIsArray() {
        // code test functionality here
        $this->assertFalse(VariableHelper::IsArray(true));
        $this->assertFalse(VariableHelper::IsArray(false));
        $this->assertFalse(VariableHelper::IsArray(1));
        $this->assertFalse(VariableHelper::IsArray(0));
        $this->assertTrue(VariableHelper::IsArray(['asdfasdf']));
        $this->assertFalse(VariableHelper::IsArray('true'));
        $this->assertTrue(VariableHelper::IsArray(['asdfasdf' => 1]));
        $this->assertFalse(VariableHelper::IsArray((object)['asdfasdf' => 1]));
    }

    /**
     * @covers Colibri\Common\VariableHelper::IsBool
     **/
    public function testVariableHelperIsBool() {
        // code test functionality here
        $this->assertTrue(VariableHelper::IsBool(true));
        $this->assertTrue(VariableHelper::IsBool(false));
        $this->assertFalse(VariableHelper::IsBool(1));
        $this->assertFalse(VariableHelper::IsBool(0));
        $this->assertFalse(VariableHelper::IsBool('true'));
    }

    /**
     * @covers Colibri\Common\VariableHelper::IsString
     **/
    public function testVariableHelperIsString() {
        // code test functionality here
        $this->assertFalse(VariableHelper::IsString(123123123));
        $this->assertTrue(VariableHelper::IsString('asdfasdfasdfasdf'));
        $this->assertFalse(VariableHelper::IsString(null));
        $this->assertTrue(VariableHelper::IsString('123123123'));
    }

    /**
     * @covers Colibri\Common\VariableHelper::IsNumeric
     **/
    public function testVariableHelperIsNumeric() {
        // code test functionality here
        $this->assertTrue(VariableHelper::IsNumeric('123123123'));
        $this->assertFalse(VariableHelper::IsNumeric('asdfasdfasdfasdf'));
        $this->assertFalse(VariableHelper::IsNumeric(null));
        $this->assertTrue(VariableHelper::IsNumeric(123123123));
    }

    /**
     * @covers Colibri\Common\VariableHelper::IsDate
     **/
    public function testVariableHelperIsDate() {
        // code test functionality here
        $this->assertTrue(VariableHelper::IsDate('12-12-2020'));
        $this->assertFalse(VariableHelper::IsDate('asdfasdfasdfasdf'));
        $this->assertFalse(VariableHelper::IsDate('12-13-2020'));
        $this->assertFalse(VariableHelper::IsDate('13/12/2020'));
        $this->assertTrue(VariableHelper::IsDate('2020-01-01'));
        $this->assertFalse(VariableHelper::IsDate(null));
        $this->assertTrue(VariableHelper::IsDate(123123123));
    }

    /**
     * @covers Colibri\Common\VariableHelper::IsTime
     **/
    public function testVariableHelperIsTime() {
        // code test functionality here
        $this->assertTrue(VariableHelper::IsTime('00:00'));
        $this->assertFalse(VariableHelper::IsTime('asdfasdfasdfasdf'));
        $this->assertTrue(VariableHelper::IsTime('12:12'));
        $this->assertFalse(VariableHelper::IsTime('35:12'));
        $this->assertFalse(VariableHelper::IsTime(null));
        $this->assertFalse(VariableHelper::IsTime(123123123));
    }

    /**
     * @covers Colibri\Common\VariableHelper::ChangeArrayValueCase
     **/
    public function testVariableHelperChangeArrayValueCase() {
        // code test functionality here
        $res = VariableHelper::ChangeArrayValueCase(['Test' => 'adsfasSSSf', 'test' => 'DSDFSDFSDF'], CASE_LOWER);
        $keys = array_values($res);
        $this->assertEquals('["adsfassssf","dsdfsdfsdf"]', json_encode($keys));

        $res = VariableHelper::ChangeArrayValueCase(['Test' => 'adsfasSSSf', 'test2' => 'ASDASdasdasd'], CASE_LOWER);
        $keys = array_values($res);
        $this->assertEquals('["adsfassssf","asdasdasdasd"]', json_encode($keys));        

        $res = VariableHelper::ChangeArrayValueCase(['Test' => 'Test', 'test2' => 'test2'], CASE_UPPER);
        $keys = array_values($res);
        $this->assertEquals('["TEST","TEST2"]', json_encode($keys));        
        
        $res = VariableHelper::ChangeArrayValueCase(null, CASE_UPPER);
        $this->assertNull($res);

        $res = VariableHelper::ChangeArrayValueCase('adfasdfasdf', CASE_UPPER);
        $this->assertNull($res);

        $res = VariableHelper::ChangeArrayValueCase(1, CASE_UPPER);
        $this->assertNull($res);
    }

    /**
     * @covers Colibri\Common\VariableHelper::ChangeArrayKeyCase
     **/
    public function testVariableHelperChangeArrayKeyCase() {
        // code test functionality here
        $res = VariableHelper::ChangeArrayKeyCase(['Test' => 1, 'test' => 2], CASE_LOWER);
        $keys = array_keys($res);
        $this->assertEquals('["test"]', json_encode($keys));

        $res = VariableHelper::ChangeArrayKeyCase(['Test' => 1, 'test2' => 2], CASE_LOWER);
        $keys = array_keys($res);
        $this->assertEquals('["test","test2"]', json_encode($keys));        

        $res = VariableHelper::ChangeArrayKeyCase(['Test' => 1, 'test2' => 2], CASE_UPPER);
        $keys = array_keys($res);
        $this->assertEquals('["TEST","TEST2"]', json_encode($keys));        
        
        $res = VariableHelper::ChangeArrayKeyCase(null, CASE_UPPER);
        $this->assertNull($res);

        $res = VariableHelper::ChangeArrayKeyCase('adfasdfasdf', CASE_UPPER);
        $this->assertNull($res);

        $res = VariableHelper::ChangeArrayKeyCase(1, CASE_UPPER);
        $this->assertNull($res);
    }

    /**
     * @covers Colibri\Common\VariableHelper::ObjectToArray
     **/
    public function testVariableHelperObjectToArray() {
        // code test functionality here
        $result = VariableHelper::ObjectToArray((object)['test' => 1]);
        $this->assertTrue(is_array($result));
        $this->assertEquals('{"test":1}', json_encode($result));

        $result = VariableHelper::ObjectToArray(['test' => 1]);
        $this->assertTrue(is_array($result));
        $this->assertEquals('{"test":1}', json_encode($result));

        $this->assertNull(VariableHelper::ObjectToArray('adfasdf'));
        $this->assertNull(VariableHelper::ObjectToArray(1));
    }

    /**
     * @covers Colibri\Common\VariableHelper::ArrayToObject
     **/
    public function testVariableHelperArrayToObject() {
        // code test functionality here
        $result = VariableHelper::ArrayToObject(['test' => 1]);
        $this->assertTrue(is_object($result));
        $this->assertEquals('{"test":1}', json_encode($result));

        $result = VariableHelper::ArrayToObject((object)['test' => 1]);
        $this->assertTrue(is_object($result));
        $this->assertEquals('{"test":1}', json_encode($result));

        $this->assertNull(VariableHelper::ArrayToObject('adfasdf'));
        $this->assertNull(VariableHelper::ArrayToObject(1));
    }

    /**
     * @covers Colibri\Common\VariableHelper::IsAssociativeArray
     **/
    public function testVariableHelperIsAssociativeArray() {
        $this->assertTrue(VariableHelper::IsAssociativeArray(['test' => 1]));
        $this->assertFalse(VariableHelper::IsAssociativeArray([1, 2, 3, 4]));
        $this->assertFalse(VariableHelper::IsAssociativeArray(1));
        $this->assertFalse(VariableHelper::IsAssociativeArray('1'));
        $this->assertFalse(VariableHelper::IsAssociativeArray((object)['test' => 1]));
    }

    /**
     * @covers Colibri\Common\VariableHelper::Bin2Hex
     **/
    public function testVariableHelperBin2Hex() {
        // code test functionality here
        $this->assertEquals('6173646661736466617364666173646661736466', VariableHelper::Bin2Hex('asdfasdfasdfasdfasdf'));
        $this->assertEquals('7361646661736466', VariableHelper::Bin2Hex('sadfasdf'));
        $this->assertEquals('', VariableHelper::Bin2Hex(null));
        $this->assertEquals('', VariableHelper::Bin2Hex(1));
    }

    /**
     * @covers Colibri\Common\VariableHelper::Hex2Bin
     **/
    public function testVariableHelperHex2Bin() {
        // code test functionality here
        $this->assertEquals('s:9:"asdfasdfa";', VariableHelper::Hex2Bin('733a393a22617364666173646661223b'));
        $this->assertEquals('', VariableHelper::Hex2Bin('sadfasdf'));
        $this->assertEquals('', VariableHelper::Hex2Bin(null));
        $this->assertEquals('', VariableHelper::Hex2Bin(1));

    }

    /**
     * @covers Colibri\Common\VariableHelper::isSerialized
     **/
    public function testVariableHelperIsSerialized() {
        // code test functionality here
        $this->assertTrue(VariableHelper::isSerialized('0x733a393a22617364666173646661223b'));
        $this->assertFalse(VariableHelper::isSerialized('sadfasdf'));
    }

    /**
     * @covers Colibri\Common\VariableHelper::Serialize
     **/
    public function testVariableHelperSerialize() {
        
        $s = VariableHelper::Serialize('asdfasdfa');
        $this->assertEquals('0x733a393a22617364666173646661223b', $s);

        $s = VariableHelper::Serialize(null);
        $this->assertEquals('0x4e3b', $s);

        $s = VariableHelper::Serialize(1);
        $this->assertEquals('0x693a313b', $s);

    }
    

    /**
     * @covers Colibri\Common\VariableHelper::Unserialize
     **/
    public function testVariableHelperUnserialize() {
        $s = VariableHelper::Unserialize('0x733a393a22617364666173646661223b');
        $this->assertEquals('asdfasdfa', $s);

        $s = VariableHelper::Unserialize('0x4e3b');
        $this->assertEquals(null, $s);

        $s = VariableHelper::Unserialize('0x693a313b');
        $this->assertEquals(1, $s);

    }

    /**
     * @covers Colibri\Common\VariableHelper::Extend
     **/
    public function testVariableHelperExtend() {
        // code test functionality here
        $res = VariableHelper::Extend(['a' => 1], ['b' => 2]);
        $this->assertEquals('{"a":1,"b":2}', json_encode($res));

        $res = VariableHelper::Extend(['a' => 1, 'b' => 123], ['b' => 2]);
        $this->assertEquals('{"a":1,"b":2}', json_encode($res));

        $res = VariableHelper::Extend(null, ['b' => 2]);
        $this->assertEquals('{"b":2}', json_encode($res));

        $res = VariableHelper::Extend(['b' => 2], null);
        $this->assertEquals('{"b":2}', json_encode($res));
    }

    /**
     * @covers Colibri\Common\VariableHelper::Coalesce
     **/
    public function testVariableHelperCoalesce() {
        $res = VariableHelper::Coalesce(null, '1');
        $this->assertEquals('1', $res);

        $res = VariableHelper::Coalesce('2', '1');
        $this->assertEquals('2', $res);
    }

    /**
     * @covers Colibri\Common\VariableHelper::ToString
     **/
    public function testVariableHelperToString() {
        // code test functionality here
        $res = VariableHelper::ToString((object)['test' => 1, 'test2' => 2, 'test3' => 'asdfasdf', 'test4' => true], '&', '=', true, 'args_');
        $this->assertEquals('args_test="1"&args_test2="2"&args_test3="asdfasdf"&args_test4="1"', $res);

        $res = VariableHelper::ToString('asdfasdf', '&', '=', true, 'args_');
        $this->assertFalse($res);
    }

    /**
     * @covers Colibri\Common\VariableHelper::FromPhpArrayOutput
     **/
    public function testVariableHelperFromPhpArrayOutput() {
        // не покрыто тестами, непонятно нужно ли вообще

    }

    /**
     * @covers Colibri\Common\VariableHelper::Sum
     **/
    public function testVariableHelperSum() {
        // code test functionality here
        $res = VariableHelper::Sum([1, 2, 3]);
        $this->assertEquals(6, $res);

        $res = VariableHelper::Sum([1, '2', 3]);
        $this->assertEquals(6, $res);

        $res = VariableHelper::Sum(null);
        $this->assertEquals(0, $res);
    }
}
