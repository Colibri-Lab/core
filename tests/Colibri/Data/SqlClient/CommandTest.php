<?php

use PHPUnit\Framework\TestCase;
use Colibri\Data\SqlClient\Command;
use Colibri\Data\SqlClient\IConnection;
use Colibri\Data\SqlClient\IDataReader;
use Colibri\Data\SqlClient\QueryInfo;
use Colibri\Utils\Logs\Logger;

class CommandTest extends TestCase
{
    public function testGetAndSetProperties()
    {
        $command = $this->getMockForAbstractClass(Command::class, ['SELECT * FROM table']);
        $command->query = 'SELECT * FROM new_table';
        $command->connection = $this->createMock(IConnection::class);
        $command->page = 2;
        $command->pagesize = 20;
        $command->params = ['param1' => 'value1'];

        $this->assertEquals('SELECT * FROM new_table', $command->query);
        $this->assertInstanceOf(IConnection::class, $command->connection);
        $this->assertEquals(2, $command->page);
        $this->assertEquals(20, $command->pagesize);
        $this->assertEquals(['param1' => 'value1'], $command->params);
    }

    public function testExecuteReader()
    {
        $command = $this->getMockForAbstractClass(Command::class, ['SELECT * FROM table']);
        $command->expects($this->once())->method('ExecuteReader')->willReturn($this->createMock(IDataReader::class));

        $this->assertInstanceOf(IDataReader::class, $command->ExecuteReader());
    }

    public function testExecuteNonQuery()
    {
        $command = $this->getMockForAbstractClass(Command::class, ['SELECT * FROM table']);
        $command->expects($this->once())->method('ExecuteNonQuery')->willReturn($this->createMock(QueryInfo::class));

        $this->assertInstanceOf(QueryInfo::class, $command->ExecuteNonQuery());
    }

    public function testPrepareQueryString()
    {
        $command = $this->getMockForAbstractClass(Command::class, ['SELECT * FROM table']);
        $command->page = 2;
        $command->pagesize = 20;

        $this->assertEquals('SELECT * FROM table limit 20, 20', $command->PrepareQueryString());
    }

    public function testMigrate()
    {
        $logger = $this->createMock(Logger::class);
        $command = $this->getMockForAbstractClass(Command::class, ['SELECT * FROM table']);
        $command->expects($this->once())->method('Migrate')->with($logger, 'storage', ['prefix' => 'prefix']);

        $command->Migrate($logger, 'storage', ['prefix' => 'prefix']);
    }

    public function testPrepareStatementThrowsException()
    {
        $this->expectException(SphinxException::class);
        $this->expectExceptionMessage('no params');

        $command = $this->getMockForAbstractClass(Command::class, ['SELECT * FROM table']);
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('_prepareStatement');
        $method->setAccessible(true);

        $method->invokeArgs($command, ['SELECT * FROM table WHERE id = [[id]]']);
    }

    public function testPrepareStatement()
    {
        $command = $this->getMockForAbstractClass(Command::class, ['SELECT * FROM table']);
        $command->params = ['id' => 1];
        $reflection = new \ReflectionClass($command);
        $method = $reflection->getMethod('_prepareStatement');
        $method->setAccessible(true);

        $result = $method->invokeArgs($command, ['SELECT * FROM table WHERE id = [[id]]']);
        $this->assertEquals('SELECT * FROM table WHERE id = 1', $result);
    }

    public function testExtractFieldInformation()
    {
        $field = [
            'Field' => 'id',
            'Type' => 'int',
            'Key' => 'PRI'
        ];

        $result = Command::ExtractFieldInformation($field);
        $this->assertEquals('id', $result->Field);
        $this->assertEquals('int', $result->Type);
        $this->assertEquals('PRI', $result->Key);
    }

    public function testExtractIndexInformation()
    {
        $index = [
            'IndexName' => 'PRIMARY',
            'AttrName' => 'id',
            'Type' => 'BTREE'
        ];

        $result = Command::ExtractIndexInformation($index);
        $this->assertEquals('PRIMARY', $result->Name);
        $this->assertEquals(['id'], $result->Columns);
        $this->assertEquals('BTREE', $result->Type);
    }

    
}
