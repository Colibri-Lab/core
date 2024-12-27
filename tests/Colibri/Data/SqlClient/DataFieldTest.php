<?php

use PHPUnit\Framework\TestCase;
use Colibri\Data\SqlClient\DataField;

class DataFieldTest extends TestCase
{
    public function testProperties()
    {
        $dataField = new DataField();
        $dataField->db = 'test_db';
        $dataField->name = 'test_name';
        $dataField->originalName = 'original_name';
        $dataField->table = 'test_table';
        $dataField->originalTable = 'original_table';
        $dataField->escaped = 'escaped_name';
        $dataField->def = 'default_value';
        $dataField->maxLength = 255;
        $dataField->length = 100;
        $dataField->flags = ['flag1', 'flag2'];
        $dataField->type = 'varchar';
        $dataField->decimals = 2;

        $this->assertEquals('test_db', $dataField->db);
        $this->assertEquals('test_name', $dataField->name);
        $this->assertEquals('original_name', $dataField->originalName);
        $this->assertEquals('test_table', $dataField->table);
        $this->assertEquals('original_table', $dataField->originalTable);
        $this->assertEquals('escaped_name', $dataField->escaped);
        $this->assertEquals('default_value', $dataField->def);
        $this->assertEquals(255, $dataField->maxLength);
        $this->assertEquals(100, $dataField->length);
        $this->assertEquals(['flag1', 'flag2'], $dataField->flags);
        $this->assertEquals('varchar', $dataField->type);
        $this->assertEquals(2, $dataField->decimals);
    }
}
