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
 * Лог файл
 */
abstract class Logger implements LoggerInterface
{

    use TEventDispatcher;

    /** Обязательное сообщение */
    const Emergency = 0;
    /** Информация */
    const Alert = 1;
    /** Критическая ошибка */
    const Critical = 2;
    /** Ошибка */
    const Error = 3;
    /** Предупреждение */
    const Warning = 4;
    /** Информирование */
    const Notice = 5;
    /** Просто информация */
    const Informational = 6;
    /** Дебаг */
    const Debug = 7;

    /**
     * Наименование лог файла
     *
     * @var mixed
     */
    protected $_device;

    /**
     * Максимальный уровень логирования
     *
     * @var integer
     */
    protected $_maxLogLevel = 7;

    /**
     * Записывает в лог данные
     *
     * @param int $level уровень ошибки
     * @param mixed $data данные
     * @return void
     */
    /**
     * @testFunction testLoggerWriteLine
     */
    abstract public function WriteLine(int $level, mixed $data): void;

    /**
     * Возвращает контент лог файла
     *
     * @return mixed
     */
    abstract public function Content(): mixed;

    /**
     * Фабрика
     *
     * @param Config|array $loggerConfig
     * @return Logger
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
     * @testFunction testLoggerAlert
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
     * @testFunction testLoggerCritical
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
     * @testFunction testLoggerError
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
     * @testFunction testLoggerWarning
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
     * @testFunction testLoggerNotice
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
     * @testFunction testLoggerInfo
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
     * @testFunction testLoggerDebug
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
     * @testFunction testLoggerLog
     */
    public function log($level, $message, array $context = array()): void
    {
        $this->WriteLine($level, ['message' => $message, 'context' => $context]);
        $this->DispatchEvent(EventsContainer::LogWriten, (object) ['type' => $level, 'message' => $message, 'context' => $context]);
    }
}