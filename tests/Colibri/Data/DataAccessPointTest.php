<?php

namespace Colibri\Tests\Data;

use Colibri\Data\DataAccessPoint;
use PHPUnit\Framework\TestCase;

class DataAccessPointTest extends TestCase
{
    public function testInsert()
    {
        $accessPointData = (object)[
            'driver' => (object)[
                'connection' => \Colibri\Data\Mysql\Connection::class,
                'config' => \Colibri\Data\Mysql\Config::class,
                'command' => \Colibri\Data\Mysql\Command::class,
                'querybuilder' => \Colibri\Data\Mysql\QueryBuilder::class,
            ],
            'database' => 'test_db'
        ];

        $dataAccessPoint = new DataAccessPoint($accessPointData);
        $result = $dataAccessPoint->Insert('test_table', ['field1' => 'value1']);

        $this->assertInstanceOf(\Colibri\Data\SqlClient\QueryInfo::class, $result);
    }
}
