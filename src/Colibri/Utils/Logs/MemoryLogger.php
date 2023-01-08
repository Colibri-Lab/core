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
use DateTime;

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
    public function __construct(int $maxLogLevel = 7, mixed $dummy = [])
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
    public function WriteLine(int $level, mixed $data): void
    {
        $now = DateTime::createFromFormat('U.u', microtime(true));
        if (!$now) {
            return;
        }
        $now = $now->format("m-d-Y H:i:s.u");

        $args = !is_array($data) ? [$data] : $data;
        if (isset($args['context'])) {
            $args['context'] = implode("\t", $args['context']);
        }
        $args = implode("\t", $args);
        $args = $now . "\t" . $args;
        $this->_device[] = $args;
    }

    /**
     * Возвращает контент лог файла
     *
     * @return mixed
     * @testFunction testMemoryLoggerContent
     */
    public function Content(): mixed
    {
        return $this->_device;
    }
}