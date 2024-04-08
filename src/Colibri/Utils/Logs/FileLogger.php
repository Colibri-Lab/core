<?php

/**
 * FileLogger
 * 
 * Represents a logger for logging messages to a file.
 * 
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2020 ColibriLab
 * @package Colibri\Utils\Logs
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
class FileLogger extends Logger
{

    /**
     * Current position in the log file.
     *
     * @var int
     */
    private int $_currentPos;

    /**
     * Indicates whether to log messages to the console as well.
     *
     * @var bool
     */
    private bool $_console;

    /**
     * The file handler.
     *
     * @var mixed
     */
    private mixed $_handler;

    /**
     * Constructor.
     *
     * @param int $maxLogLevel The maximum log level.
     * @param mixed $device The file name.
     * @param bool $console Whether to log messages to the console as well. Default is false.
     */
    public function __construct(int $maxLogLevel = 7, mixed $device = '', $console = false)
    {
        if (!$device) {
            $device = '_cache/log/unnamed.log';
        }
        $this->_device = StringHelper::StartsWith($device, '/') ? $device : App::$webRoot . $device;
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

        if (!File::Exists($this->_device)) {
            File::Create($this->_device, true, '777');
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

        if ($this->_console) {
            Debug::Out($args);
        }

        $fi = new File($this->_device);
        if ($fi->size > 1048576) {
            File::Move($this->_device, $this->_device . '.' . $now);
            File::Create($this->_device, true, '777');
        }

        File::Append($this->_device, $args);
    }

    /**
     * Retrieves the content of the log file.
     *
     * @return mixed The content of the log file.
     */
    public function Content(): mixed
    {
        return File::Read($this->_device);
    }

    /**
     * Opens the log file for sequential reading.
     *
     * @param int $position The start position for reading.
     * @return void
     */
    public function Open(int $position = 0): void
    {
        if (!file_exists($this->_device)) {
            touch($this->_device);
        }
        $this->_handler = fopen($this->_device, 'r+');
        $this->_currentPos = $position;
    }

    /**
     * Closes the log file.
     *
     * @return void
     */
    public function Close(): void
    {
        fclose($this->_handler);
        $this->_handler = false;
        $this->_currentPos = 0;
    }

    /**
     * Reads the last messages in the log starting from the last read position, returning them as an array of strings.
     *
     * @return array An array of log message strings.
     */
    public function Read(): array
    {
        $results = array();
        fseek($this->_handler, $this->_currentPos);
        while ($string = fgets($this->_handler)) {
            $results[] = $string;
            $this->_currentPos += strlen($string);
        }
        return $results;
    }
}