<?php

use PHPUnit\Framework\TestCase;
use Colibri\Collections\CollectionException;

class CollectionExceptionTest extends TestCase
{
    public function testExceptionMessage(): void
    {
        $this->expectException(CollectionException::class);
        $this->expectExceptionMessage('Test message');
        throw new CollectionException('Test message');
    }
}
