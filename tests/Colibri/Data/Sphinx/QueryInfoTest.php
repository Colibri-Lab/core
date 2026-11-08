<?php

use PHPUnit\Framework\TestCase;
use Colibri\Data\Sphinx\QueryInfo;
use Colibri\Data\SqlClient\QueryInfo as SqlQueryInfo;

class SphinxQueryInfoTest extends TestCase
{
    public function testExtendsSqlQueryInfo(): void
    {
        $info = new QueryInfo('SELECT', 0, 5, '', 'SELECT * FROM index');
        $this->assertInstanceOf(SqlQueryInfo::class, $info);
    }

    public function testConstructor(): void
    {
        $info = new QueryInfo('SELECT', 0, 10, '', 'SELECT * FROM articles_idx WHERE MATCH(\'test\')');
        $this->assertEquals('SELECT', $info->type);
        $this->assertEquals(0, $info->insertid);
        $this->assertEquals(10, $info->affected);
        $this->assertEquals('', $info->error);
    }

    public function testErrorProperty(): void
    {
        $info = new QueryInfo('SELECT', 0, 0, 'index not found', 'SELECT ...');
        $this->assertEquals('index not found', $info->error);
    }
}
