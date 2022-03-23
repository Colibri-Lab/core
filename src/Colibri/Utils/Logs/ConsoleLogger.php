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
    public function __construct($maxLogLevel = 7, $dummy = '')
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
    public function __get(string $prop)
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
    public function WriteLine($level, $data)
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

        echo Debug::ROut($args);
    }

    /**
     * Возвращает контент лог файла
     *
     * @return mixed
     * @testFunction testFileLoggerContent
     */
    public function Content()
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
    public function Open($position = 0)
    {
        throw new LoggerException('ConsoleLogger does not support this action');
    }

    /**
     * Закрывает лог файл
     *
     * @return void
     * @testFunction testFileLoggerClose
     */
    public function Close()
    {
        throw new LoggerException('ConsoleLogger does not support this action');
    }

    /**
     * Читает последние сообщения в логе начиная с позиции последнего чтения, возвращает в виде массива строк
     *
     * @return array массив строк лога
     * @testFunction testFileLoggerRead
     */
    public function Read()
    {
        throw new LoggerException('ConsoleLogger does not support this action');
    }
}
