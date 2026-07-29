<?php

use PHPUnit\Framework\TestCase;
use Colibri\Data\Storages\Fields\ValueField;

class ValueFieldTest extends TestCase
{
    public function testConstructorCreatesInstance(): void
    {
        $field = new ValueField('key', 'Key Title');
        $this->assertInstanceOf(ValueField::class, $field);
    }

    public function testValueProperty(): void
    {
        $field = new ValueField('active', 'Active');
        $this->assertEquals('active', $field->value);
    }

    public function testTitleProperty(): void
    {
        $field = new ValueField('active', 'Active Status');
        $this->assertEquals('Active Status', $field->title);
    }

    public function testSetValueProperty(): void
    {
        $field = new ValueField('old', 'Old Title');
        $field->value = 'new';
        $this->assertEquals('new', $field->value);
    }

    public function testSetTitleProperty(): void
    {
        $field = new ValueField('key', 'Old Title');
        $field->title = 'New Title';
        $this->assertEquals('New Title', $field->title);
    }

    public function testToString(): void
    {
        $field = new ValueField('test_val', 'Test Value');
        $this->assertEquals('test_val', $field->ToString());
    }

    public function testToStringWithEmptyValue(): void
    {
        $field = new ValueField('', 'Empty');
        $this->assertEquals('', $field->ToString());
    }

    public function testJsonSerializable(): void
    {
        $field = new ValueField('val', 'Title');
        $json = json_encode($field);
        $this->assertIsString($json);
        $data = json_decode($json, true);
        $this->assertIsArray($data);
    }

    public function testTitleAsArray(): void
    {
        $titles = ['en' => 'Active', 'ru' => 'Активный'];
        $field = new ValueField('active', $titles);
        $this->assertEquals($titles, $field->title);
    }

    public function testTitleAsObject(): void
    {
        $titleObj = (object)['en' => 'Active'];
        $field = new ValueField('active', $titleObj);
        $this->assertEquals($titleObj, $field->title);
    }

    public function testUnknownPropertyReturnsNull(): void
    {
        $field = new ValueField('val', 'Title');
        $this->assertNull($field->unknown);
    }
}
