<?php

/**
 * ConsoleLogger
 * 
 * Represents a logger for logging messages to the console.
 * 
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2020 ColibriLab
 * @package Colibri\Utils\Logs
 */
namespace Colibri\Utils\Logs;

use Colibri\Utils\Debug;
use DateTime;

/**
 * Represents a logger for logging messages to the console.
 * 
 * This class extends the abstract Logger class and provides functionality to log messages to the console.
 * 
 */
class ConsoleLogger extends Logger
{

    /**
     * Constructor.
     *
     * @param int $maxLogLevel The maximum log level.
     * @param mixed $dummy Dummy parameter (not used).
     */
    public function __construct(int $maxLogLevel = 7, mixed $dummy = '')
    {
        $this->_device = '';
        $this->_maxLogLevel = $maxLogLevel;
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
     * Writes a log line to the console.
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

        $args = !is_array($data) ? [$data] : $data;
        $args[] = "\n";
        if (isset($args['context'])) {
            $args['context'] = implode("\t", $args['context']);
        }
        $args = $now . "\t" . implode("\t", $args);

        $str = Debug::ROut($args);
        switch ($level) {
            case Logger::Alert:
            case Logger::Emergency:
            case Logger::Critical:
            case Logger::Error:
                echo "\033[31m$str \033[0m\n";
                break;
            case Logger::Notice:
                echo "\033[32m$str \033[0m\n";
                break;
            case Logger::Warning:
                echo "\033[33m$str \033[0m\n";
                break;
            case Logger::Informational:
                echo "\033[36m$str \033[0m\n";
                break;
            default:
                echo $str;
                break;
        }
    }

    /**
     * Retrieves the content of the log file (not applicable for ConsoleLogger).
     *
     * @return mixed The content of the log file.
     */
    public function Content(): mixed
    {
        return '';
    }

    /**
     * Opens the log file for sequential reading (not applicable for ConsoleLogger).
     *
     * @param int $position The start position for reading.
     * @return void
     * @throws LoggerException
     */
    public function Open(int $position = 0): void
    {
        throw new LoggerException('ConsoleLogger does not support this action');
    }

    /**
     * Closes the log file (not applicable for ConsoleLogger).
     *
     * @return void
     */
    public function Close(): void
    {
        throw new LoggerException('ConsoleLogger does not support this action');
    }

    /**
     * Reads the last messages in the log starting from the last read position, returning them as an array of strings (not applicable for ConsoleLogger).
     *
     * @return array An array of log message strings.
     */
    public function Read(): array
    {
        throw new LoggerException('ConsoleLogger does not support this action');
    }
}