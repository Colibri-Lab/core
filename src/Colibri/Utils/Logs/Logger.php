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

use Colibri\Utils\Config\Config;
use Psr\Log\LoggerInterface;
use Colibri\Events\TEventDispatcher;
use Colibri\Events\EventsContainer;

/**
 * Represents a logger for logging messages.
 *
 * This abstract class provides a structure for logging messages. It implements the PSR-3 LoggerInterface and uses an event dispatcher to handle logging events.
 * @class
 * @implements LoggerInterface
 * @uses TEventDispatcher
 * 
 */
abstract class Logger implements LoggerInterface
{
    use TEventDispatcher;

    /** 
     * Emergency level constant
     * @const int
     * @public 
     */
    public const Emergency = 0;
    /** 
     * Alert level constant
     * @const int
     * @public
     */
    public const Alert = 1;
    /** 
     * Critical level constant
     * @const int
     * @public
     */
    public const Critical = 2;
    /** 
     * Error level constant
     * @const int
     * @public
     */
    public const Error = 3;
    /** 
     * Warning level constant
     * @const int
     * @public
     */
    public const Warning = 4;
    /** 
     * Notice level constant
     * @const int
     * @public
     */
    public const Notice = 5;
    /** 
     * Informational level constant
     * @const int
     * @public
     */
    public const Informational = 6;
    /** 
     * Debug level constant
     * @const int
     * @public
     */
    public const Debug = 7;

    /**
     * The name of the log file.
     *
     * @var mixed
     * @protected
     */
    protected $_device;

    /**
     * The maximum log level.
     *
     * @var integer
     * @protected
     */
    protected $_maxLogLevel = 7;

    /**
     * Writes a log line.
     *
     * @param int $level The log level.
     * @param mixed $data The log data.
     * @return void
     * @abstract
     * @public
     */
    abstract public function WriteLine(int $level, mixed $data): void;

    /**
     * Retrieves the content of the log file.
     *
     * @return mixed The content of the log file.
     * @abstract
     * @public
     */
    abstract public function Content(): mixed;

    /**
     * Creates a logger instance based on the provided configuration.
     *
     * @param Config|array $loggerConfig The logger configuration.
     * @return Logger The logger instance.
     * @throws LoggerException When an invalid logger type is provided.
     * @static
     * @public
     */
    public static function Create(Config|array $loggerConfig): Logger
    {
        if ($loggerConfig instanceof Config) {
            $loggerType = $loggerConfig->Query('type')->GetValue();
            $loggerLevel = $loggerConfig->Query('level')->GetValue();
            $loggerDevice = $loggerConfig->Query('device')->AsObject();
        } elseif (is_array($loggerConfig)) {
            $loggerType = $loggerConfig['type'];
            $loggerLevel = $loggerConfig['level'];
            $loggerDevice = $loggerConfig['device'];
            if (is_array($loggerDevice)) {
                $loggerDevice = (object) $loggerDevice;
            }
        }

        if (!$loggerType) {
            throw new LoggerException('Invalid logger type');
        }

        $className = 'Colibri\\Utils\\Logs\\' . $loggerType . 'Logger';
        if (!\class_exists($className)) {
            throw new LoggerException('Invalid logger type');
        }

        return new $className($loggerLevel, $loggerDevice);

    }


    /**
     * System is unusable.
     *
     * @param string $message
     * @param array $context
     * @return void
     * @testFunction testLoggerEmergency
     * @public
     */
    public function emergency($message, array $context = array()): void
    {
        $this->WriteLine(Logger::Emergency, ['message' => $message, 'context' => $context]);
        $this->DispatchEvent(EventsContainer::LogWriten, (object) ['type' => Logger::Emergency, 'message' => $message, 'context' => $context]);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array $context
     * @return void
     * @public
     */
    public function alert($message, array $context = array()): void
    {
        $this->WriteLine(Logger::Alert, ['message' => $message, 'context' => $context]);
        $this->DispatchEvent(EventsContainer::LogWriten, (object) ['type' => Logger::Alert, 'message' => $message, 'context' => $context]);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array $context
     * @return void
     * @public
     */
    public function critical($message, array $context = array()): void
    {
        $this->WriteLine(Logger::Critical, ['message' => $message, 'context' => $context]);
        $this->DispatchEvent(EventsContainer::LogWriten, (object) ['type' => Logger::Critical, 'message' => $message, 'context' => $context]);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array $context
     * @return void
     * @public
     */
    public function error($message, array $context = array()): void
    {
        $this->WriteLine(Logger::Error, ['message' => $message, 'context' => $context]);
        $this->DispatchEvent(EventsContainer::LogWriten, (object) ['type' => Logger::Error, 'message' => $message, 'context' => $context]);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array $context
     * @return void
     * @public
     */
    public function warning($message, array $context = array()): void
    {
        $this->WriteLine(Logger::Warning, ['message' => $message, 'context' => $context]);
        $this->DispatchEvent(EventsContainer::LogWriten, (object) ['type' => Logger::Warning, 'message' => $message, 'context' => $context]);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array $context
     * @return void
     * @public
     */
    public function notice($message, array $context = array()): void
    {
        $this->WriteLine(Logger::Notice, ['message' => $message, 'context' => $context]);
        $this->DispatchEvent(EventsContainer::LogWriten, (object) ['type' => Logger::Notice, 'message' => $message, 'context' => $context]);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array $context
     * @return void
     * @public
     */
    public function info($message, array $context = array()): void
    {
        $this->WriteLine(Logger::Informational, ['message' => $message, 'context' => $context]);
        $this->DispatchEvent(EventsContainer::LogWriten, (object) ['type' => Logger::Informational, 'message' => $message, 'context' => $context]);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array $context
     * @return void
     * @public
     */
    public function debug($message, array $context = array()): void
    {
        $this->WriteLine(Logger::Debug, ['message' => $message, 'context' => $context]);
        $this->DispatchEvent(EventsContainer::LogWriten, (object) ['type' => Logger::Debug, 'message' => $message, 'context' => $context]);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return void
     * @public
     */
    public function log($level, $message, array $context = array()): void
    {
        $this->WriteLine($level, ['message' => $message, 'context' => $context]);
        $this->DispatchEvent(EventsContainer::LogWriten, (object) ['type' => $level, 'message' => $message, 'context' => $context]);
    }
}
