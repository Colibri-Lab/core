<?php

use PHPUnit\Framework\TestCase;
use Colibri\Modules\ModuleManager;
use Colibri\Utils\Config\Config;

class ModuleManagerTest extends TestCase
{
    public function testCreate()
    {
        $instance = ModuleManager::Create();
        $this->assertInstanceOf(ModuleManager::class, $instance);
    }

    public function testInitialize()
    {
        $moduleManager = ModuleManager::Create();
        $moduleManager->Initialize();
        $this->assertNotEmpty($moduleManager->list);
    }

    public function testInitModule()
    {
        $moduleManager = ModuleManager::Create();
        $config = new Config(['entry' => 'TestModule']);
        $module = $moduleManager->InitModule($config);
        $this->assertNotNull($module);
    }

    public function testGet()
    {
        $moduleManager = ModuleManager::Create();
        $module = $moduleManager->Get('TestModule');
        $this->assertNotNull($module);
    }

    public function testConfig()
    {
        $moduleManager = ModuleManager::Create();
        $config = $moduleManager->Config('TestModule');
        $this->assertInstanceOf(Config::class, $config);
    }

    public function testGetPermissions()
    {
        $moduleManager = ModuleManager::Create();
        $permissions = $moduleManager->GetPermissions();
        $this->assertIsArray($permissions);
    }

    public function testGetPaths()
    {
        $moduleManager = ModuleManager::Create();
        $paths = $moduleManager->GetPaths();
        $this->assertIsArray($paths);
    }

    public function testGetPathsFromModuleConfig()
    {
        $moduleManager = ModuleManager::Create();
        $paths = $moduleManager->GetPathsFromModuleConfig();
        $this->assertIsArray($paths);
    }

    public function testGetTemplates()
    {
        $moduleManager = ModuleManager::Create();
        $templates = $moduleManager->GetTemplates();
        $this->assertIsArray($templates);
    }
}
