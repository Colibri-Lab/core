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
use Colibri\Common\DateHelper;
use Colibri\IO\FileSystem\File;
use Colibri\Utils\Debug;

/**
 * Лог файл
 * @testFunction testFileLogger
 */
class ConsoleLogger extends Logger
{

    /**
     * Конструктор
     *
     * @param int $maxLogLevel Уровень логирования
     * @param mixed $device название файла
     */
    public function __construct(int $maxLogLevel = 7, mixed $dummy = '')
    {
        $this->_device = '';
        $this->_maxLogLevel = $maxLogLevel;
    }

    /**
     * Getter
     *
     * @param string $prop
     * @return mixed
     * @testFunction testFileLogger__get
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
     * Записывает в лог данные
     *
     * @param int $level уровень ошибки
     * @param mixed $data данные
     * @return void
     * @testFunction testFileLoggerWriteLine
     */
    public function WriteLine(int $level, mixed $data): void
    {

        if ($level > $this->_maxLogLevel) {
            return;
        }

        $args = !is_array($data) ? [$data] : $data;
        $args[] = "\n";
        if (isset($args['context'])) {
            $args['context'] = implode("\t", $args['context']);
        }
        $args = DateHelper::ToDbString(microtime(true), '%Y-%m-%d-%H-%M-%S-%f') . "\t" . implode("\t", $args);

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
     * Возвращает контент лог файла
     *
     * @return mixed
     * @testFunction testFileLoggerContent
     */
    public function Content(): mixed
    {
        return '';
    }

    /**
     * Открывает лог файл для последовательного чтения
     *
     * @param integer $position стартовая позиция для чтения
     * @return void
     * @throws LoggerException
     * @testFunction testFileLoggerOpen
     */
    public function Open(int $position = 0): void
    {
        throw new LoggerException('ConsoleLogger does not support this action');
    }

    /**
     * Закрывает лог файл
     *
     * @return void
     * @testFunction testFileLoggerClose
     */
    public function Close(): void
    {
        throw new LoggerException('ConsoleLogger does not support this action');
    }

    /**
     * Читает последние сообщения в логе начиная с позиции последнего чтения, возвращает в виде массива строк
     *
     * @return array массив строк лога
     * @testFunction testFileLoggerRead
     */
    public function Read(): array
    {
        throw new LoggerException('ConsoleLogger does not support this action');
    }
}
