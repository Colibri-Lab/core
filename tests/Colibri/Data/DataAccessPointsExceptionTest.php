<?php

namespace Colibri\Tests\Data;

use Colibri\Data\DataAccessPointsException;
use PHPUnit\Framework\TestCase;

class DataAccessPointsExceptionTest extends TestCase
{
    public function testExceptionMessage()
    {
        $this->expectException(DataAccessPointsException::class);
        $this->expectExceptionMessage('Test exception message');

        throw new DataAccessPointsException('Test exception message');
    }
}
