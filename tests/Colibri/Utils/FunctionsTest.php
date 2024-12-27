<?php

use PHPUnit\Framework\TestCase;
use Colibri\Utils\Debug;

class FunctionsTest extends TestCase
{
    public function testDdFunction()
    {
        $this->expectOutputString('test');
        dd('test');
    }

    public function testDdxFunction()
    {
        $this->expectOutputString('test');
        ddx('test');
    }

    public function testDddFunction()
    {
        $this->expectOutputString('test');
        ddd('test');
    }

    public function testDddxFunction()
    {
        $this->expectOutputString('test');
        dddx('test');
    }

    public function testDdrxFunction()
    {
        $result = ddrx('test');
        $this->assertStringContainsString('test', $result);
    }

    public function testRunxFunction()
    {
        $result = runx('echo', ['Hello, World!']);
        $this->assertNotNull($result);
    }

    public function testKillxFunction()
    {
        $pid = runx('sleep', ['10']);
        $this->assertNotNull($pid);
        killx((int)$pid);
    }

    public function testPidxFunction()
    {
        $pid = runx('sleep', ['10']);
        $this->assertNotNull($pid);
        $pids = pidx('sleep');
        $this->assertContains((int)$pid, $pids);
        killx((int)$pid);
    }

    public function testAppDebugFunction()
    {
        $this->expectOutputString('test');
        app_debug('test');
    }

    public function testAppInfoFunction()
    {
        $this->expectOutputString('test');
        app_info('test');
    }

    public function testAppEmergencyFunction()
    {
        $this->expectOutputString('test');
        app_emergency('test');
    }
}
