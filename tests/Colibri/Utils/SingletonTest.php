<?php

use PHPUnit\Framework\TestCase;
use Colibri\Utils\Singleton;

class SingletonTest extends TestCase
{
    public function testSingletonInstance()
    {
        $instance1 = Singleton::Create();
        $instance2 = Singleton::Create();
        
        $this->assertInstanceOf(Singleton::class, $instance1);
        $this->assertSame($instance1, $instance2);
    }
}
