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

use Colibri\Common\DateHelper;

/**
 * Класс для работы с логами в памяти
 * @testFunction testMemoryLogger
 */
class MemoryLogger extends Logger
{

    /**
     * Конструктор
     *
     * @param integer $maxLogLevel
     * @param array $dummy
     */
    public function __construct($maxLogLevel = 7, $dummy = [])
    {
        $this->_maxLogLevel = $maxLogLevel;
        $this->_device = [];
    }

    /**
     * Записывает в лог данные
     *
     * @param int $level уровень ошибки
     * @param mixed $data данные
     * @return void
     * @testFunction testMemoryLoggerWriteLine
     */
    public function WriteLine($level, $data)
    {
        $args = !is_array($data) ? [$data] : $data;
        if(isset($args['context'])) {
            $args['context'] = implode("\t", $args['context']);
        }
        $args = implode("\t", $args);
        $args = DateHelper::ToDbString(microtime(true), '%Y-%m-%d-%H-%M-%S-%f') . "\t" . $args; 
        $this->_device[] = $args;
    }

    /**
     * Возвращает контент лог файла
     *
     * @return mixed
     * @testFunction testMemoryLoggerContent
     */
    public function Content()
    {
        return $this->_device;
    }
}
