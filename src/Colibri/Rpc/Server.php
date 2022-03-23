<?php

/**
 * Класс сервера RPC
 *
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Rpc
 * @version 1.0.0
 *
 */

namespace Colibri\Rpc;

use Colibri\Web\Server as WebServer;

/**
 * Класс запросщик, вызывается в сервисе /.rpc/index.php
 * @testFunction testServer
 */
class Server extends WebServer
{

    const NotRpcQuery = 'This is not a Rpc query';

    /**
     * @testFunction testServerRun
     */
    public function Run($cmd, $default = '')
    {
        if (strstr($cmd, '.rpc') === false) {
            $this->_responseWithError(Server::NotRpcQuery, 404, $cmd);
        }
        parent::Run(str_replace('/.rpc', '', $cmd), $default);
    }
}
