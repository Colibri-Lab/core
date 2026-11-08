<?php

use PHPUnit\Framework\TestCase;
use Colibri\Data\MySql\QueryInfo;
use Colibri\Data\SqlClient\QueryInfo as SqlQueryInfo;

class MySqlQueryInfoTest extends TestCase
{
    public function testExtendsSqlQueryInfo(): void
    {
        $info = new QueryInfo('SELECT', 0, 5, '', 'SELECT * FROM users');
        $this->assertInstanceOf(SqlQueryInfo::class, $info);
    }

    public function testConstructor(): void
    {
        $info = new QueryInfo('INSERT', 42, 1, '', 'INSERT INTO users ...');
        $this->assertEquals('INSERT', $info->type);
        $this->assertEquals(42, $info->insertid);
        $this->assertEquals(1, $info->affected);
        $this->assertEquals('', $info->error);
        $this->assertEquals('INSERT INTO users ...', $info->query);
    }

    public function testTypeProperty(): void
    {
        $info = new QueryInfo('DELETE', 0, 3, '', 'DELETE FROM users WHERE id=1');
        $this->assertEquals('DELETE', $info->type);
    }

    public function testAffectedProperty(): void
    {
        $info = new QueryInfo('UPDATE', 0, 7, '', 'UPDATE users SET ...');
        $this->assertEquals(7, $info->affected);
    }
}
