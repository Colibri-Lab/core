<?php

use PHPUnit\Framework\TestCase;
use Colibri\Data\MySql\QueryBuilder;
use Colibri\Data\MySql\Connection;

class MySqlQueryBuilderTest extends TestCase
{
    private QueryBuilder $queryBuilder;

    protected function setUp(): void
    {
        $connectionMock = $this->createMock(Connection::class);
        $this->queryBuilder = new QueryBuilder($connectionMock);
    }

    public function testCreateInsert(): void
    {
        $sql = $this->queryBuilder->CreateInsert('users', ['name' => 'John', 'email' => 'john@example.com']);
        $this->assertStringContainsString('insert into', strtolower($sql));
        $this->assertStringContainsString('users', $sql);
        $this->assertStringContainsString('name', $sql);
        $this->assertStringContainsString('email', $sql);
    }

    public function testCreateReplace(): void
    {
        $sql = $this->queryBuilder->CreateReplace('users', ['id' => 1, 'name' => 'John']);
        $this->assertStringContainsString('replace into', strtolower($sql));
        $this->assertStringContainsString('users', $sql);
    }

    public function testCreateUpdate(): void
    {
        $sql = $this->queryBuilder->CreateUpdate('users', 'id=1', ['name' => 'Jane']);
        $this->assertStringContainsString('update', strtolower($sql));
        $this->assertStringContainsString('users', $sql);
        $this->assertStringContainsString('id=1', $sql);
    }

    public function testCreateDelete(): void
    {
        $sql = $this->queryBuilder->CreateDelete('users', 'id=1');
        $this->assertStringContainsString('delete', strtolower($sql));
        $this->assertStringContainsString('users', $sql);
        $this->assertStringContainsString('id=1', $sql);
    }

    public function testCreateSelect(): void
    {
        $sql = $this->queryBuilder->CreateSelect('users', '*', 'id=1', 'name asc');
        $this->assertStringContainsString('select', strtolower($sql));
        $this->assertStringContainsString('users', $sql);
    }

    public function testCreateShowTables(): void
    {
        $sql = $this->queryBuilder->CreateShowTables();
        $this->assertIsString($sql);
        $this->assertStringContainsString('show', strtolower($sql));
    }

    public function testCreateDrop(): void
    {
        $sql = $this->queryBuilder->CreateDrop('users');
        $this->assertStringContainsString('drop', strtolower($sql));
        $this->assertStringContainsString('users', $sql);
    }

    public function testCreateBegin(): void
    {
        $sql = $this->queryBuilder->CreateBegin();
        $this->assertIsString($sql);
    }

    public function testCreateCommit(): void
    {
        $sql = $this->queryBuilder->CreateCommit();
        $this->assertIsString($sql);
        $this->assertStringContainsString('commit', strtolower($sql));
    }

    public function testCreateRollback(): void
    {
        $sql = $this->queryBuilder->CreateRollback();
        $this->assertIsString($sql);
        $this->assertStringContainsString('rollback', strtolower($sql));
    }

    public function testCreateSoftDeleteQuery(): void
    {
        $sql = $this->queryBuilder->CreateSoftDeleteQuery('datedeleted', 'users');
        $this->assertIsString($sql);
    }
}
