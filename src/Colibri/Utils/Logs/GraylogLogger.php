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

use Colibri\Utils\Debug;

/**
 * Класс для работы с GrayLog
 * @testFunction testGraylogLogger
 */
class GraylogLogger extends Logger
{

    /**
     * Конструктор
     *
     * @param integer $maxLogLevel
     * @param string $device
     */
    public function __construct($maxLogLevel = 7, $device = '')
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
     * Записывает в лог данные
     *
     * @param int $level уровень ошибки
     * @param mixed $data данные
     * @return void
     * @testFunction testGraylogLoggerWriteLine
     */
    public function WriteLine($level, $data)
    {


        $data = (object)$data;
        if ($level > $this->_maxLogLevel) {
            return;
        }

        $host = '';
        if (isset($this->_device->host)) {
            $host = $this->_device->host;
        } else if (isset($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
        }

        $gelf = [
            'version' => '1.1',
            'host' => $host,
            'short_message' => $data->message,
            'full_message' => $data->context,
            'level' => $level,
        ];
        $gelf = array_merge($gelf, (array)$data);

        $data = json_encode((object)$gelf, JSON_UNESCAPED_UNICODE);

        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if ($socket) {
            socket_sendto($socket, $data, strlen($data), 0, $this->_device->server, $this->_device->port);
        } else {
            throw new LoggerException('Не смогли создать сокен в GrayLog', 500);
        }
    }

    /**
     * Возвращает контент лог файла
     *
     * @return mixed
     * @testFunction testGraylogLoggerContent
     */
    public function Content()
    {
        return null;
    }
}
