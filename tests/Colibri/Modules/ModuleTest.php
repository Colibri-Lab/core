<?php

use PHPUnit\Framework\TestCase;
use Colibri\Modules\Module;
use Colibri\Utils\Config\Config;

class ModuleTest extends TestCase
{
    public function testCreate()
    {
        $config = new Config([]);
        $module = Module::Create($config);
        $this->assertInstanceOf(Module::class, $module);
    }

    public function testConfig()
    {
        $config = new Config([]);
        $module = Module::Create($config);
        $this->assertInstanceOf(Config::class, $module->Config());
    }

    public function testGetPathsFromModuleConfig()
    {
        $config = new Config([]);
        $module = Module::Create($config);
        $paths = $module->GetPathsFromModuleConfig();
        $this->assertIsArray($paths);
    }

    public function testGetPermissions()
    {
        $config = new Config([]);
        $module = Module::Create($config);
        $permissions = $module->GetPermissions();
        $this->assertIsArray($permissions);
    }
}
