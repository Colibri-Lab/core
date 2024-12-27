<?php

use PHPUnit\Framework\TestCase;
use Colibri\Modules\ModuleException;

class ModuleExceptionTest extends TestCase
{
    public function testModuleException()
    {
        $this->expectException(ModuleException::class);
        throw new ModuleException('Test exception');
    }
}
