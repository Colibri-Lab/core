<?php

/**
 * Rpc
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Rpc
 * @deprecated
 */

namespace Colibri\Rpc;

use Colibri\Web\Server as WebServer;

class Server extends WebServer
{

    const NotRpcQuery = 'This is not a Rpc query';

    public function Run(string $cmd, string $default = ''): void
    {
        if (strstr($cmd, '.rpc') === false) {
            $this->_responseWithError(Server::NotRpcQuery, 404, $cmd);
        }
        parent::Run(str_replace('/.rpc', '', $cmd), $default);
    }
}