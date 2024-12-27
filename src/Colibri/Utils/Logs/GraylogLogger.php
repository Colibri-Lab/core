<?php

/**
 * Logs
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2020 ColibriLab
 * @package Colibri\Utils\Logs
 *
 */

namespace Colibri\Utils\Logs;

/**
 * Class for working with GrayLog.
 *
 * This class extends the abstract Logger class and provides functionality to log messages to a GrayLog server.
 *
 */
class GraylogLogger extends Logger
{
    /**
     * Constructor.
     *
     * @param integer $maxLogLevel The maximum log level.
     * @param mixed $device The device information containing the server and port.
     * @throws LoggerException If the device information is invalid.
     */
    public function __construct(int $maxLogLevel = 7, mixed $device = '')
    {
        $this->_maxLogLevel = $maxLogLevel;
        if (!is_object($device) && !is_array($device)) {
            throw new LoggerException('Invalid device information');
        }

        if (!isset($device->server) || !isset($device->port)) {
            throw new LoggerException('Invalid device information');
        }

        $this->_device = $device;
    }

    /**
     * Writes a log line to GrayLog.
     *
     * @param int $level The log level.
     * @param mixed $data The log data.
     * @return void
     */
    public function WriteLine(int $level, mixed $data): void
    {


        $data = (object) $data;
        if ($level > $this->_maxLogLevel) {
            return;
        }

        $host = '';
        if (isset($this->_device->host)) {
            $host = $this->_device->host;
        } elseif (isset($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
        }

        $gelf = [
            'version' => '1.1',
            'host' => $host,
            'short_message' => $data->message,
            'full_message' => $data->context,
            'level' => $level,
        ];
        $gelf = array_merge($gelf, (array) $data);

        $data = json_encode((object) $gelf, JSON_UNESCAPED_UNICODE);

        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if ($socket) {
            socket_sendto($socket, $data, strlen($data), 0, $this->_device->server, $this->_device->port);
        } else {
            throw new LoggerException('Не смогли создать сокен в GrayLog', 500);
        }
    }

    /**
     * Returns the content of the log file (not applicable for GrayLog).
     *
     * @return mixed null
     */
    public function Content(): mixed
    {
        return null;
    }
}
