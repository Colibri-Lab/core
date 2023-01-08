<?php

/**
 * Конфигурация
 *
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Utils\Config
 * @version 1.0.0
 *
 */

namespace Colibri\Utils\Config;

use Colibri\App;
use Colibri\Common\VariableHelper;
use Colibri\IO\FileSystem\File;
use Colibri\IO\FileSystem\Finder;
use Iterator;
use IteratorAggregate;
use Colibri\Collections\ArrayListIterator;

/**
 * Класс для работы с конфиг файлами в yaml
 * @testFunction testConfig
 */
class Config implements IteratorAggregate
{

    private string $_file = '';

    /**
     * Тут хранятся загруженные данные конфиг файлами
     *
     * @var object
     */
    private $_configData;

    /**
     * Конструктор
     *
     * @param mixed $fileName файл или данные
     * @param boolean $isFile указываем файл передали или строку
     */
    public function __construct(mixed $fileName, bool $isFile = true, string $file = '')
    {

        $this->_file = $file;

        if (is_array($fileName) || is_object($fileName)) {
            $this->_configData = $fileName;
        } elseif (is_numeric($fileName) || $fileName) {
            try {
                if ($isFile && file_exists(App::$appRoot . '/config/' . $fileName)) {
                    $path = App::$appRoot . '/config/' . $fileName;
                    $this->_configData = \yaml_parse_file($path);
                    $this->_file = $fileName;
                } elseif ($isFile && file_exists($fileName)) {
                    $path = $fileName;
                    $this->_configData = \yaml_parse_file($path);
                    $this->_file = $fileName;
                } elseif (!VariableHelper::IsEmpty(trim($fileName))) {
                    $this->_configData = \yaml_parse($fileName);
                } else {
                    $this->_configData = [];
                }
            } catch (\Throwable $e) {
                if ($isFile) {
                    $message = 'Error reading config file: ' . $path . '. Error message: ' . $e->getMessage();
                } else {
                    $message = 'Error reading config: ' . $fileName . '. Error message: ' . $e->getMessage();
                }
                throw new ConfigException($message);
            }
        }
    }

    /**
     * Загрузить yaml файл
     * @param mixed $fileName
     * @return Config
     * @testFunction testConfigLoadFile
     */
    public static function LoadFile(string $fileName): Config
    {
        return new Config($fileName);
    }

    /**
     * Загрузить yaml строку
     *
     * @param string $yamlData
     * @return Config
     * @testFunction testConfigLoad
     */
    public static function Load(string $yamlData): Config
    {
        return new Config($yamlData, false);
    }

    /**
     * Функция обработки команд в yaml
     *
     * @param mixed $value значение
     * @return mixed
     * @testFunction testConfig_prepareValue
     */
    private function _prepareValue(mixed $value, string $file = ''): mixed
    {
        if (is_object($value) || is_array($value)) {
            return [$value, $file];
        }

        $return = $value;
        if (strstr($value, 'include')) {
            $res = preg_match('/include\((.*)\)/', $value, $matches);
            if ($res > 0) {
                if (File::Exists(App::$appRoot . '/config/' . $matches[1])) {
                    $return = \yaml_parse_file(App::$appRoot . '/config/' . $matches[1]);
                    $file = $matches[1];
                } elseif (File::Exists(App::$appRoot . $matches[1])) {
                    $return = \yaml_parse_file(App::$appRoot . $matches[1]);
                    $file = $matches[1];
                } elseif (File::Exists($matches[1])) {
                    $return = \yaml_parse_file($matches[1]);
                    $file = $matches[1];
                } else {
                    $return = null;
                }
            } else {
                $return = null;
            }
        }
        return [$return, $file];
    }

    /**
     * Запрос значения из конфигурации
     *
     * пути указываются в javascript нотации
     * например: settings.item[0].info или settings.item.buh.notice_email
     *
     * @param string $item строковое представление пути в конфигурационном файле
     * @param mixed $default значение по умолчанию, если путь не найден
     * @return ConfigItemsList|Config
     * @testFunction testConfigQuery
     */
    public function Query(string $item, mixed $default = null): ConfigItemsList|Config
    {
        $command = explode('.', $item);

        $file = $this->_file;
        try {
            $data = $this->_configData;
            foreach ($command as $commandItem) {
                if (strstr($commandItem, '[') !== false) {
                    // массив
                    $res = preg_match('/(.+)\[(\d+)\]/', $commandItem, $matches);
                    if ($res > 0) {
                        $cmdItem = $matches[1];
                        $cmdIndex = $matches[2];
                        [$data, $file] = $this->_prepareValue($data[$cmdItem][$cmdIndex], $file);
                    } else {
                        throw new ConfigException('Illeval query: ' . $item);
                    }
                } else {
                    if (!isset($data[$commandItem])) {
                        throw new ConfigException('Illeval query: ' . $item);
                    }
                    // не массив
                    [$data, $file] = $this->_prepareValue($data[$commandItem], $file);
                }
            }
        } catch (ConfigException $e) {
            if ($default) {
                $data = $default;
            } else {
                throw $e;
            }
        }

        if (is_array($data) && !$this->isKindOfObject($data)) {
            return new ConfigItemsList($data, $file);
        } else {
            return new Config($data, false, $file);
        }
    }

    /**
     * Вернуть внутренние данные в виде обьекта
     *
     * @return object
     * @testFunction testConfigAsObject
     */
    public function AsObject(): object|string|null
    {
        if (is_array($this->_configData)) {
            return (object) VariableHelper::ArrayToObject($this->_configData);
        }
        return $this->_configData;
    }

    /**
     * Вернуть внутренние данные в виде массива
     *
     * @return array
     * @testFunction testConfigAsArray
     */
    public function AsArray(): ?array
    {
        return (array) $this->_configData;
    }

    /**
     * Вернуть хранимое значение
     * Внимание! Если текущие данные массив или обьект, то будет возвращен null
     *
     * @return mixed
     * @testFunction testConfigGetValue
     */
    public function GetValue(): mixed
    {
        if ($this->isKindOfObject($this->_configData) || is_array($this->_configData)) {
            return null;
        }
        return $this->_configData;
    }

    /**
     * Проверяет на ассотиативность массива
     *
     * @param array $param
     * @return boolean
     * @testFunction testConfigIsKindOfObject
     */
    public function isKindOfObject(string|array |null $param): bool
    {
        if (is_null($param)) {
            return false;
        }

        $param = (array) $param;
        foreach ($param as $key => $value) {
            if (is_string($key)) {
                return true;
            }
        }

        return false;
    }

    public function Save(string $fileName = ''): bool
    {
        $fileName = $fileName ?: $this->_file;
        $path = App::$appRoot . '/config/' . $fileName;
        return \yaml_emit_file($path, $this->_configData, \YAML_UTF8_ENCODING, \YAML_ANY_BREAK);
    }

    /**
     * Собирает все файлы конфигураций в папке /config
     */
    static function Enumerate(): array
    {

        $ret = [];
        $finder = new Finder();
        $files = $finder->Files(App::$appRoot . '/config/', '/.yaml/');
        foreach ($files as $file) {
            $ret[] = str_replace(App::$appRoot . '/config/', '', $file->path);
        }
        return $ret;

    }

    public function GetFile(): string
    {
        return $this->_file;
    }

    public function Set(string $item, mixed $value): void
    {
        try {
            $command = explode('.', $item);
            if ($value !== null) {
                $command = '$this->_configData[\'' . implode('\'][\'', $command) . '\']=$value;';
            } else {
                $command = 'unset($this->_configData[\'' . implode('\'][\'', $command) . '\']);';
            }
            eval($command);
        } catch (\Throwable $e) {
            throw new ConfigException('Illeval query: ' . $item);
        }
    }

    public function Item(int $index): mixed
    {
        $keys = array_keys($this->_configData);
        if ($index < count($keys)) {
            return $this->Query($keys[$index]);
        }
        return null;
    }

    function getIterator()
    {
        return new ArrayListIterator($this);
    }

}