<?php

use PHPUnit\Framework\TestCase;
use Colibri\Data\SqlClient\QueryInfo;

class QueryInfoTest extends TestCase
{
    public function testConstructor()
    {
        $queryInfo = new QueryInfo('SELECT', 1, 10, 'No error', 'SELECT * FROM table');

        $this->assertEquals('SELECT', $queryInfo->type);
        $this->assertEquals(1, $queryInfo->insertid);
        $this->assertEquals(10, $queryInfo->affected);
        $this->assertEquals('No error', $queryInfo->error);
        $this->assertEquals('SELECT * FROM table', $queryInfo->query);
    }
}
