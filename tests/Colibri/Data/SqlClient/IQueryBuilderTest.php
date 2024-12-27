<?php

use PHPUnit\Framework\TestCase;
use Colibri\Data\SqlClient\IQueryBuilder;

class IQueryBuilderTest extends TestCase
{

    public function testCreateInsert()
    {
        $mock = $this->createMock(IQueryBuilder::class);
        $mock->method('CreateInsert')->willReturn('INSERT INTO table (column) VALUES (value)');

        $this->assertEquals('INSERT INTO table (column) VALUES (value)', $mock->CreateInsert('table', ['column' => 'value']));
    }

    public function testCreateReplace()
    {
        $mock = $this->createMock(IQueryBuilder::class);
        $mock->method('CreateReplace')->willReturn('REPLACE INTO table (column) VALUES (value)');

        $this->assertEquals('REPLACE INTO table (column) VALUES (value)', $mock->CreateReplace('table', ['column' => 'value']));
    }

    public function testCreateInsertOrUpdate()
    {
        $mock = $this->createMock(IQueryBuilder::class);
        $mock->method('CreateInsertOrUpdate')->willReturn('INSERT INTO table (column) VALUES (value) ON DUPLICATE KEY UPDATE column=VALUES(column)');

        $this->assertEquals('INSERT INTO table (column) VALUES (value) ON DUPLICATE KEY UPDATE column=VALUES(column)', $mock->CreateInsertOrUpdate('table', ['column' => 'value']));
    }

    public function testCreateBatchInsert()
    {
        $mock = $this->createMock(IQueryBuilder::class);
        $mock->method('CreateBatchInsert')->willReturn('INSERT INTO table (column1, column2) VALUES (value1, value2), (value3, value4)');

        $this->assertEquals('INSERT INTO table (column1, column2) VALUES (value1, value2), (value3, value4)', $mock->CreateBatchInsert('table', [['column1' => 'value1', 'column2' => 'value2'], ['column1' => 'value3', 'column2' => 'value4']]));
    }

    public function testCreateUpdate()
    {
        $mock = $this->createMock(IQueryBuilder::class);
        $mock->method('CreateUpdate')->willReturn('UPDATE table SET column=value WHERE condition');

        $this->assertEquals('UPDATE table SET column=value WHERE condition', $mock->CreateUpdate('table', 'condition', ['column' => 'value']));
    }

    public function testCreateDelete()
    {
        $mock = $this->createMock(IQueryBuilder::class);
        $mock->method('CreateDelete')->willReturn('DELETE FROM table WHERE condition');

        $this->assertEquals('DELETE FROM table WHERE condition', $mock->CreateDelete('table', 'condition'));
    }

    public function testCreateShowTables()
    {
        $mock = $this->createMock(IQueryBuilder::class);
        $mock->method('CreateShowTables')->willReturn('SHOW TABLES');

        $this->assertEquals('SHOW TABLES', $mock->CreateShowTables());
    }

    public function testCreateShowField()
    {
        $mock = $this->createMock(IQueryBuilder::class);
        $mock->method('CreateShowField')->willReturn('SHOW COLUMNS FROM table');

        $this->assertEquals('SHOW COLUMNS FROM table', $mock->CreateShowField('table'));
    }

    public function testCreateShowIndexes()
    {
        $mock = $this->createMock(IQueryBuilder::class);
        $mock->method('CreateShowIndexes')->willReturn('SHOW INDEXES FROM table');

        $this->assertEquals('SHOW INDEXES FROM table', $mock->CreateShowIndexes('table'));
    }

    public function testCreateBegin()
    {
        $mock = $this->createMock(IQueryBuilder::class);
        $mock->method('CreateBegin')->willReturn('BEGIN');

        $this->assertEquals('BEGIN', $mock->CreateBegin());
    }

    public function testCreateCommit()
    {
        $mock = $this->createMock(IQueryBuilder::class);
        $mock->method('CreateCommit')->willReturn('COMMIT');

        $this->assertEquals('COMMIT', $mock->CreateCommit());
    }

    public function testCreateRollback()
    {
        $mock = $this->createMock(IQueryBuilder::class);
        $mock->method('CreateRollback')->willReturn('ROLLBACK');

        $this->assertEquals('ROLLBACK', $mock->CreateRollback());
    }

    public function testCreateDefaultStorageTable()
    {
        $mock = $this->createMock(IQueryBuilder::class);
        $mock->method('CreateDefaultStorageTable')->willReturn('CREATE TABLE table (id INT)');

        $this->assertEquals('CREATE TABLE table (id INT)', $mock->CreateDefaultStorageTable('table'));
    }

    public function testCreateDrop()
    {
        $mock = $this->createMock(IQueryBuilder::class);
        $mock->method('CreateDrop')->willReturn('DROP TABLE table');

        $this->assertEquals('DROP TABLE table', $mock->CreateDrop('table'));
    }

    public function testCreateFieldForQuery()
    {
        $mock = $this->createMock(IQueryBuilder::class);
        $mock->method('CreateFieldForQuery')->willReturn('table.field');

        $this->assertEquals('table.field', $mock->CreateFieldForQuery('field', 'table'));
    }
}

