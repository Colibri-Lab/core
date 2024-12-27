<?php

namespace Colibri\Tests\Data;

use Colibri\Data\DataAccessPointIterator;
use PHPUnit\Framework\TestCase;

class DataAccessPointIteratorTest extends TestCase
{
    public function testIterator()
    {
        $dataAccessPoints = $this->createMock(\Colibri\Data\DataAccessPoints::class);
        $iterator = new DataAccessPointIterator($dataAccessPoints);

        $this->assertInstanceOf(\Iterator::class, $iterator);
    }
}
