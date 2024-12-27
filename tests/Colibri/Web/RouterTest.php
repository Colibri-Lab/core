<?php

use PHPUnit\Framework\TestCase;
use Colibri\Web\Router;

class RouterTest extends TestCase
{
    public function testUpdateRequest()
    {
        $_SERVER['REQUEST_URI'] = '/test/uri';
        $router = new Router();
        $router->UpdateRequest();
        $this->assertEquals('/test/uri', $_SERVER['REQUEST_URI']);
    }

    public function testUri()
    {
        $router = new Router();
        $result = $router->Uri('/test/uri');
        $this->assertEquals('/test/uri', $result);
    }
}
