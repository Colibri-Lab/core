<?php

use PHPUnit\Framework\TestCase;
use Colibri\Events\EventsContainer;

class EventsContainerTest extends TestCase
{
    public function testAppReadyConstant(): void
    {
        $this->assertEquals('app.ready', EventsContainer::AppReady);
    }

    public function testAppInitializingConstant(): void
    {
        $this->assertEquals('app.initializing', EventsContainer::AppInitializing);
    }

    public function testRequestReadyConstant(): void
    {
        $this->assertEquals('request.ready', EventsContainer::RequestReady);
    }

    public function testResponseReadyConstant(): void
    {
        $this->assertEquals('response.ready', EventsContainer::ResponseReady);
    }

    public function testModuleManagerReadyConstant(): void
    {
        $this->assertEquals('modulemanager.ready', EventsContainer::ModuleManagerReady);
    }

    public function testSecurityManagerReadyConstant(): void
    {
        $this->assertEquals('securitymanager.ready', EventsContainer::SecurityManagerReady);
    }

    public function testConstantsAreStrings(): void
    {
        $this->assertIsString(EventsContainer::AppReady);
        $this->assertIsString(EventsContainer::AppInitializing);
        $this->assertIsString(EventsContainer::RequestReady);
        $this->assertIsString(EventsContainer::ResponseReady);
        $this->assertIsString(EventsContainer::ModuleManagerReady);
        $this->assertIsString(EventsContainer::SecurityManagerReady);
    }
}
