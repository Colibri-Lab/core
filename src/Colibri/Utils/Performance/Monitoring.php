<?php

/**
 * Performance
 *
 * @package Colibri\Utils\Performance
 * @author Vahan P. Grigoryan
 * @copyright 2020 ColibriLab
 * 
 */
namespace Colibri\Utils\Performance;

use Colibri\Common\DateHelper;
use Colibri\Common\StringHelper;
use Colibri\Utils\Logs\Logger;

/**
 * Class for monitoring performance.
 */
class Monitoring
{

    const EveryTimer = 0;
    const FullStackOnly = 1;
    const Never = 2;

    /**
     * @var array Holds timers data
     */
    private $_timers;

    /**
     * @var Logger Logger instance
     */
    private $_logger;

    /**
     * @var mixed Frequency of logging
     */
    private $_logging;

    /**
     * @var int Log level
     */

    private $_loglevel;

    /**
     * @var mixed Additional data
     */
    private $_aditionalData;

    /**
     * Constructor
     * 
     * @param Logger $logger The logger instance
     * @param int $logLevel The log level
     * @param mixed $logging The logging frequency
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
     * Destructor
     * 
     * @return void
     */
    public function __destruct()
    {
        $this->EndTimer('root');
        if ($this->_logging == self::FullStackOnly) {
            $this->Log($this->_loglevel);
        }
    }

    /**
     * Collects additional data
     * 
     * @return mixed
     */
    private function _collectAditionalData()
    {
        return [
            'uri' => $_SERVER['REQUEST_URI'],
            'file' => $_SERVER['SCRIPT_FILENAME'],
            'time' => $_SERVER['REQUEST_TIME_FLOAT']
        ];
    }

    /**
     * Starts a timer
     * 
     * @param string $name The name of the timer
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
     * Ends a timer
     * 
     * @param string $name The name of the timer
     * @return void
     */
    public function EndTimer($name, ?\Closure $if = null)
    {
        $timer = $this->_timers[$name];
        $timer->end = \microtime(true);
        $timer->memoryAfter = \memory_get_usage(true);
        $timer->interval = (int) (($timer->end - $timer->start) * 1000);
        $this->_timers[$name] = $timer;

        if ($this->_logging == self::EveryTimer && (!$if || $if($timer))) {
            $this->Log($this->_loglevel, $name);
        }
    }

    /**
     * Logs performance data
     * 
     * @param int $logLevel The log level
     * @param string|null $name The name of the timer
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

    /**
     * Generates a log message
     * 
     * @param string $name The name of the timer
     * @param mixed $timer The timer data
     * @return string The log message
     */
    private function _message($name, $timer)
    {
        return DateHelper::ToDbString($this->_aditionalData['time']) . '. ' .
            'Timer «' . $name . '». ' . "\t" .
            'Time: ' . DateHelper::TimeToString((int)$timer->start) . ' - ' . DateHelper::TimeToString((int)$timer->end) . '. ' . "\t" .
            'Delta: ' . $timer->interval . ' ms, ' . "\t" .
            'Мemory: ' . StringHelper::FormatFileSize((int)$timer->memoryBefore) . ' - ' . StringHelper::FormatFileSize((int)$timer->memoryAfter) . "\t" .
            'Uri: ' . $this->_aditionalData['uri'] . '.';
    }

}