<?php

use PHPUnit\Framework\TestCase;
use Colibri\Data\PgSql\QueryInfo;
use Colibri\Data\SqlClient\QueryInfo as SqlQueryInfo;

class PgSqlQueryInfoTest extends TestCase
{
    public function testExtendsSqlQueryInfo(): void
    {
        $info = new QueryInfo('SELECT', 0, 3, '', 'SELECT * FROM users');
        $this->assertInstanceOf(SqlQueryInfo::class, $info);
    }

    public function testConstructor(): void
    {
        $info = new QueryInfo('INSERT', 1, 1, '', 'INSERT INTO users ...');
        $this->assertEquals('INSERT', $info->type);
        $this->assertEquals(1, $info->insertid);
        $this->assertEquals(1, $info->affected);
        $this->assertEquals('', $info->error);
    }

    public function testErrorProperty(): void
    {
        $info = new QueryInfo('SELECT', 0, 0, 'table not found', 'SELECT ...');
        $this->assertEquals('table not found', $info->error);
    }
}
