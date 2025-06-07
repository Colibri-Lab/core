<?php

use PHPUnit\Framework\TestCase;
use Colibri\Modules\ModuleManager;
use Colibri\Utils\Config\Config;

class ModuleManagerTest extends TestCase
{
    public function testCreate()
    {
        $instance = ModuleManager::Instance();
        $this->assertInstanceOf(ModuleManager::class, $instance);
    }

    public function testInitialize()
    {
        $moduleManager = ModuleManager::Instance();
        $moduleManager->Initialize();
        $this->assertNotEmpty($moduleManager->list);
    }

    public function testInitModule()
    {
        $moduleManager = ModuleManager::Instance();
        $config = new Config(['entry' => 'TestModule']);
        $module = $moduleManager->InitModule($config);
        $this->assertNotNull($module);
    }

    public function testGet()
    {
        $moduleManager = ModuleManager::Instance();
        $module = $moduleManager->Get('TestModule');
        $this->assertNotNull($module);
    }

    public function testConfig()
    {
        $moduleManager = ModuleManager::Instance();
        $config = $moduleManager->Config('TestModule');
        $this->assertInstanceOf(Config::class, $config);
    }

    public function testGetPermissions()
    {
        $moduleManager = ModuleManager::Instance();
        $permissions = $moduleManager->GetPermissions();
        $this->assertIsArray($permissions);
    }

    public function testGetPaths()
    {
        $moduleManager = ModuleManager::Instance();
        $paths = $moduleManager->GetPaths();
        $this->assertIsArray($paths);
    }

    public function testGetPathsFromModuleConfig()
    {
        $moduleManager = ModuleManager::Instance();
        $paths = $moduleManager->GetPathsFromModuleConfig();
        $this->assertIsArray($paths);
    }

    public function testGetTemplates()
    {
        $moduleManager = ModuleManager::Instance();
        $templates = $moduleManager->GetTemplates();
        $this->assertIsArray($templates);
    }
}
