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

use Colibri\App;
use Colibri\IO\FileSystem\File;
use Colibri\Utils\Debug;
use Colibri\Common\StringHelper;
use DateTime;

/**
 * Represents a logger for logging messages to a file.
 *
 * This class extends the abstract Logger class and provides functionality to log messages to a file.
 *
 */
class CallbackBasedLogger extends Logger
{

    /**
     * Indicates whether to log messages to the console as well.
     *
     * @var bool
     */
    private bool $_console;
   
    /**
     * Constructor.
     *
     * @param int $maxLogLevel The maximum log level.
     * @param mixed $eventHandler The file name.
     * @param bool $console Whether to log messages to the console as well. Default is false.
     */
    public function __construct(int $maxLogLevel = 7, mixed $eventHandler = '', $console = false)
    {
        if (!$eventHandler) {
            $eventHandler = fn() => null;
        }
        $this->_device = $eventHandler;
        $this->_maxLogLevel = $maxLogLevel;
        $this->_console = $console;
    }

    /**
     * Getter method.
     *
     * @param string $prop The property name.
     * @return mixed The value of the property.
     */
    public function __get(string $prop): mixed
    {
        $prop = strtolower($prop);
        switch ($prop) {
            case 'device':
                return $this->_device;
            case 'position':
                return $this->_currentPos;
            default:
                break;
        }
        return null;
    }

    /**
     * Writes a log line to the file.
     *
     * @param int $level The log level.
     * @param mixed $data The log data.
     * @return void
     */
    public function WriteLine(int $level, mixed $data): void
    {

        if ($level > $this->_maxLogLevel) {
            return;
        }

        $now = DateTime::createFromFormat('U.u', microtime(true));
        if (!$now) {
            return;
        }

        $now = $now->format("m-d-Y H:i:s.u");

        $args = !\is_array($data) ? [$data] : $data;
        $args['now'] = $now;

        if ($this->_console) {
            Debug::Out($args);
        }

        ($this->_device)($args, $level);
    }

    /**
     * Retrieves the content of the log file.
     *
     * @return mixed The content of the log file.
     */
    public function Content(): mixed
    {
        return null;
    }

}
