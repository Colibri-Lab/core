<?php

use PHPUnit\Framework\TestCase;
use Colibri\App;
use Colibri\Utils\Config\Config;
use Colibri\Events\EventDispatcher;
use Colibri\Modules\ModuleManager;
use Colibri\Utils\Logs\Logger;
use Colibri\Utils\Performance\Monitoring;
use Colibri\Web\Request;
use Colibri\Web\Response;
use Colibri\Web\Router;

class AppTest extends TestCase
{
    public function testAppCreate()
    {
        $app = App::Instance();
        $this->assertInstanceOf(App::class, $app);
    }

    public function testAppSingleton()
    {
        $app1 = App::Instance();
        $app2 = App::Instance();
        $this->assertSame($app1, $app2);
    }

    public function testAppInitialization()
    {
        $app = App::Instance();
        $this->assertNotNull(App::$config);
        $this->assertNotNull(App::$log);
        $this->assertNotNull(App::$eventDispatcher);
        $this->assertNotNull(App::$moduleManager);
        $this->assertNotNull(App::$monitoring);
        $this->assertNotNull(App::$request);
        $this->assertNotNull(App::$response);
        $this->assertNotNull(App::$router);
    }

    public function testGetPermissions()
    {
        $app = App::Instance();
        $permissions = $app->GetPermissions();
        $this->assertIsArray($permissions);
        $this->assertArrayHasKey('app', $permissions);
        $this->assertArrayHasKey('app.load', $permissions);
    }
}
