<?php

use PHPUnit\Framework\TestCase;
use Colibri\Data\Sphinx\QueryBuilder;
use Colibri\Data\Sphinx\Connection;

class SphinxQueryBuilderTest extends TestCase
{
    private QueryBuilder $queryBuilder;

    protected function setUp(): void
    {
        $connectionMock = $this->createMock(Connection::class);
        $this->queryBuilder = new QueryBuilder($connectionMock);
    }

    public function testCreateInsert(): void
    {
        $sql = $this->queryBuilder->CreateInsert('articles_idx', ['id' => 1, 'title' => 'Test']);
        $this->assertStringContainsString('insert', strtolower($sql));
        $this->assertStringContainsString('articles_idx', $sql);
    }

    public function testCreateUpdate(): void
    {
        $sql = $this->queryBuilder->CreateUpdate('articles_idx', 'id=1', ['title' => 'Updated']);
        $this->assertStringContainsString('update', strtolower($sql));
        $this->assertStringContainsString('articles_idx', $sql);
    }

    public function testCreateDelete(): void
    {
        $sql = $this->queryBuilder->CreateDelete('articles_idx', 'id=1');
        $this->assertStringContainsString('delete', strtolower($sql));
        $this->assertStringContainsString('articles_idx', $sql);
    }

    public function testCreateSelect(): void
    {
        $sql = $this->queryBuilder->CreateSelect('articles_idx', '*', 'id=1', 'id asc');
        $this->assertStringContainsString('select', strtolower($sql));
        $this->assertStringContainsString('articles_idx', $sql);
    }

    public function testCreateDrop(): void
    {
        $sql = $this->queryBuilder->CreateDrop('articles_idx');
        $this->assertStringContainsString('drop', strtolower($sql));
        $this->assertStringContainsString('articles_idx', $sql);
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
    }

    public function testCreateRollback(): void
    {
        $sql = $this->queryBuilder->CreateRollback();
        $this->assertIsString($sql);
    }

    public function testCreateShowTables(): void
    {
        $sql = $this->queryBuilder->CreateShowTables();
        $this->assertIsString($sql);
    }
}
