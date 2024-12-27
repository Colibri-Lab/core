<?php

namespace Colibri\Tests\Data;

use Colibri\Data\DataAccessPoints;
use Colibri\Data\DataAccessPointsException;
use PHPUnit\Framework\TestCase;

class DataAccessPointsTest extends TestCase
{
    public function testCreate()
    {
        $dataAccessPoints = DataAccessPoints::Create();
        $this->assertInstanceOf(DataAccessPoints::class, $dataAccessPoints);
    }

    public function testGetUnknownAccessPoint()
    {
        $this->expectException(DataAccessPointsException::class);
        $dataAccessPoints = DataAccessPoints::Create();
        $dataAccessPoints->Get('unknown');
    }
}
