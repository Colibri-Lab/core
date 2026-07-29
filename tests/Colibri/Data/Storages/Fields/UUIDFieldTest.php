<?php

use PHPUnit\Framework\TestCase;
use Colibri\Data\Storages\Fields\UUIDField;

class UUIDFieldTest extends TestCase
{
    private string $testUuid = '550e8400-e29b-41d4-a716-446655440000';

    public function testConstructorCreatesInstance(): void
    {
        $field = new UUIDField();
        $this->assertInstanceOf(UUIDField::class, $field);
    }

    public function testPackAndUnpack(): void
    {
        $binary = UUIDField::Pack($this->testUuid);
        $this->assertIsString($binary);
        $unpacked = UUIDField::Unpack($binary);
        $this->assertEquals($this->testUuid, $unpacked);
    }

    public function testPackReturnsBinaryString(): void
    {
        $binary = UUIDField::Pack($this->testUuid);
        $this->assertEquals(16, strlen($binary));
    }

    public function testBinaryProperty(): void
    {
        $binary = UUIDField::Pack($this->testUuid);
        $field = new UUIDField($binary);
        $this->assertEquals($binary, $field->binary);
    }

    public function testStringProperty(): void
    {
        $binary = UUIDField::Pack($this->testUuid);
        $field = new UUIDField($binary);
        $this->assertEquals($this->testUuid, $field->string);
    }

    public function testSetBinaryProperty(): void
    {
        $field = new UUIDField();
        $binary = UUIDField::Pack($this->testUuid);
        $field->binary = $binary;
        $this->assertEquals($binary, $field->binary);
    }

    public function testSetStringProperty(): void
    {
        $field = new UUIDField();
        $field->string = $this->testUuid;
        $this->assertEquals($this->testUuid, $field->string);
    }

    public function testToString(): void
    {
        $binary = UUIDField::Pack($this->testUuid);
        $field = new UUIDField($binary);
        $this->assertEquals($this->testUuid, (string) $field);
    }

    public function testJsonSerialize(): void
    {
        $binary = UUIDField::Pack($this->testUuid);
        $field = new UUIDField($binary);
        $json = json_encode($field);
        $this->assertEquals('"' . $this->testUuid . '"', $json);
    }

    public function testParamTypeName(): void
    {
        $this->assertEquals('string', UUIDField::ParamTypeName());
    }

    public function testNull(): void
    {
        $this->assertNull(UUIDField::null());
    }

    public function testUnknownPropertyReturnsNull(): void
    {
        $field = new UUIDField();
        $this->assertNull($field->unknown);
    }
}
