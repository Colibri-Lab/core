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
class FileLogger extends Logger
{

    /**
     * Текущая позиция
     * @var int
     */
    private int $_currentPos;

    /**
     * Конструктор
     *
     * @param int $maxLogLevel Уровень логирования
     * @param mixed $device название файла
     */
    public function __construct(int $maxLogLevel = 7, mixed $device = '')
    {
        if(!$device) {
            $device = '_cache/unnamed.log';
        }
        $this->_device = File::Exists($device) ? $device : App::$webRoot . $device;
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

        if (!File::Exists($this->_device)) {
            File::Create($this->_device, true, '777');
        }

        $args = !is_array($data) ? [$data] : $data;
        $args[] = "\n";
        if (isset($args['context'])) {
            $args['context'] = implode("\t", $args['context']);
        }
        $args = DateHelper::ToDbString(microtime(true), '%Y-%m-%d-%H-%M-%S-%f') . "\t" . implode("\t", $args);

        $fi = new File($this->_device);
        if ($fi->size > 1048576) {
            File::Move($this->_device, $this->_device . '.' . DateHelper::ToDbString(microtime(true), '%Y-%m-%d-%H-%M-%S-%f'));
            File::Create($this->_device, true, '777');
        }

        File::Append($this->_device, $args);
    }

    /**
     * Возвращает контент лог файла
     *
     * @return mixed
     * @testFunction testFileLoggerContent
     */
    public function Content(): mixed
    {
        return File::Read($this->_device);
    }

    /**
     * Открывает лог файл для последовательного чтения
     *
     * @param integer $position стартовая позиция для чтения
     * @return void
     * @testFunction testFileLoggerOpen
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
     * Закрывает лог файл
     *
     * @return void
     * @testFunction testFileLoggerClose
     */
    public function Close(): void
    {
        fclose($this->_handler);
        $this->_handler = false;
        $this->_currentPos = 0;
    }

    /**
     * Читает последние сообщения в логе начиная с позиции последнего чтения, возвращает в виде массива строк
     *
     * @return array массив строк лога
     * @testFunction testFileLoggerRead
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
