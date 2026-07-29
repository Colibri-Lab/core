<?php

use PHPUnit\Framework\TestCase;
use Colibri\Data\NoSqlClient\QueryInfo;

class NoSqlClientQueryInfoTest extends TestCase
{
    public function testConstructorCreatesInstance(): void
    {
        $info = new QueryInfo('SELECT', 0, 5, '', 'SELECT * FROM collection');
        $this->assertInstanceOf(QueryInfo::class, $info);
    }

    public function testTypeProperty(): void
    {
        $info = new QueryInfo('INSERT', 42, 1, '', 'INSERT INTO ...');
        $this->assertEquals('INSERT', $info->type);
    }

    public function testInsertIdProperty(): void
    {
        $info = new QueryInfo('INSERT', 42, 1, '', 'INSERT INTO ...');
        $this->assertEquals(42, $info->insertid);
    }

    public function testAffectedProperty(): void
    {
        $info = new QueryInfo('UPDATE', 0, 10, '', 'UPDATE ...');
        $this->assertEquals(10, $info->affected);
    }

    public function testErrorProperty(): void
    {
        $info = new QueryInfo('SELECT', 0, 0, 'collection not found', 'SELECT ...');
        $this->assertEquals('collection not found', $info->error);
    }

    public function testQueryProperty(): void
    {
        $query = 'db.collection.find({})';
        $info = new QueryInfo('SELECT', 0, 5, '', $query);
        $this->assertEquals($query, $info->query);
    }

    public function testEmptyError(): void
    {
        $info = new QueryInfo('DELETE', 0, 3, '', 'DELETE ...');
        $this->assertEquals('', $info->error);
    }
}
