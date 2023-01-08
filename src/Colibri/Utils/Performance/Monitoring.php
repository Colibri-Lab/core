<?php

namespace Colibri\Utils\Performance;

use Colibri\Common\DateHelper;
use Colibri\Common\StringHelper;
use Colibri\Utils\Logs\Logger;

/**
 * Класс для мониторинга
 * @author Vahan P. Grigoryan
 * @package Colibri\Utils\Performance
 */
class Monitoring
{

    const EveryTimer = 0;
    const FullStackOnly = 1;
    const Never = 2;

    /**
     * Массив данных
     * @var array
     */
    private $_timers;

    /**
     * Логгер
     * @var Logger
     */
    private $_logger;

    /**
     * Частота логирования
     * @var mixed
     */
    private $_logging;

    /**
     * Уровень логирования
     * @var int
     */
    private $_loglevel;

    /**
     * Дополнительные данные
     * @var mixed
     */
    private $_aditionalData;

    /**
     * Конструктор
     * @param Logger $logger
     * @return void
     */
    public function __construct($logger, $logLevel = Logger::Debug, $logging = self::EveryTimer)
    {
        $this->_logger = $logger;
        $this->_timers = [];
        $this->_logging = $logging;
        $this->_loglevel = $logLevel;
        $this->_aditionalData = $this->_collectAditionalData();
        $this->StartTimer('root');
    }

    /**
     * Деструктор
     * @return void
     */
    public function __destruct()
    {
        $this->EndTimer('root');
        if ($this->_logging == self::FullStackOnly) {
            $this->Log($this->_loglevel);
        }
    }

    private function _collectAditionalData()
    {
        return [
            'uri' => $_SERVER['REQUEST_URI'],
            'file' => $_SERVER['SCRIPT_FILENAME'],
            'time' => $_SERVER['REQUEST_TIME_FLOAT']
        ];
    }

    /**
     * Начать сбор
     * @param string $name название брейкпоинта
     * @return void
     */
    public function StartTimer($name)
    {
        $this->_timers[$name] = (object) [
            'start' => \microtime(true),
            'end' => null,
            'memoryBefore' => \memory_get_usage(true),
            'memoryAfter' => null,
            'interval' => 0,
            'memoryInterval' => 0,
        ];
    }

    /**
     * Закончить сбор
     * @param string $name название брейкпоинта
     * @return void
     */
    public function EndTimer($name)
    {
        $timer = $this->_timers[$name];
        $timer->end = \microtime(true);
        $timer->memoryAfter = \memory_get_usage(true);
        $timer->interval = (int) (($timer->end - $timer->start) * 1000);
        $this->_timers[$name] = $timer;

        if ($this->_logging == self::EveryTimer) {
            $this->Log($this->_loglevel, $name);
        }
    }

    /**
     * Записать в лог
     * @param int $logLevel уровень логирования
     * @param string $name название брейкпоинта
     * @return void
     */
    public function Log($logLevel, $name = null)
    {
        if ($name) {
            $timer = $this->_timers[$name];
            $this->_logger->WriteLine($logLevel, $this->_message($name, $timer));
        } else {
            foreach ($this->_timers as $timer) {
                $this->_logger->WriteLine($logLevel, $this->_message($name, $timer));
            }
        }
    }

    private function _message($name, $timer)
    {
        return DateHelper::ToDbString($this->_aditionalData['time']) . '. ' .
            'Timer «' . $name . '». ' . "\t" .
            'Time: ' . DateHelper::TimeToString($timer->start) . ' - ' . DateHelper::TimeToString($timer->end) . '. ' . "\t" .
            'Delta: ' . $timer->interval . ' ms, ' . "\t" .
            'Мemory: ' . StringHelper::FormatFileSize($timer->memoryBefore) . ' - ' . StringHelper::FormatFileSize($timer->memoryAfter) . "\t" .
            'Uri: ' . $this->_aditionalData['uri'] . '.';
    }

}