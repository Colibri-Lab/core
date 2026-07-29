<?php

use PHPUnit\Framework\TestCase;
use Colibri\Data\Solr\Exception;
use Colibri\Data\DataAccessPointsException;
use Colibri\AppException;

class SolrExceptionTest extends TestCase
{
    public function testIsException(): void
    {
        $e = new Exception('solr error');
        $this->assertInstanceOf(\Exception::class, $e);
        $this->assertInstanceOf(AppException::class, $e);
        $this->assertInstanceOf(DataAccessPointsException::class, $e);
        $this->assertInstanceOf(Exception::class, $e);
    }

    public function testMessage(): void
    {
        $e = new Exception('query failed');
        $this->assertEquals('query failed', $e->getMessage());
    }

    public function testCode(): void
    {
        $e = new Exception('error', 400);
        $this->assertEquals(400, $e->getCode());
    }

    public function testThrowAndCatch(): void
    {
        $this->expectException(Exception::class);
        throw new Exception('test');
    }
}
