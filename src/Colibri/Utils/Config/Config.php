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
use Colibri\Common\ObjectHelper;
use Colibri\Common\StringHelper;
use Colibri\Common\VariableHelper;
use Colibri\IO\FileSystem\File;

/**
 * Класс для работы с конфиг файлами в yaml
 * @testFunction testConfig
 */
class Config
{

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
    public function __construct(mixed $fileName, bool $isFile = true)
    {
        if (is_array($fileName) || is_object($fileName)) {
            $this->_configData = $fileName;
        }
        else if ($fileName) {
            $path = App::$appRoot . '/config/' . $fileName;
            try {
                if ($isFile && file_exists($path)) {
                    $this->_configData = \yaml_parse_file($path);
                }
                else if (!VariableHelper::IsEmpty(trim($fileName))) {
                    $this->_configData = \yaml_parse($fileName);
                }
                else {
                    $this->_configData = [];
                }
            }
            catch (\Throwable $e) {
                if ($isFile) {
                    $message = 'Error reading config file: ' . $path . '. Error message: ' . $e->getMessage();
                }
                else {
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
    private function _prepareValue(mixed $value): mixed
    {
        if (is_object($value) || is_array($value)) {
            return $value;
        }

        $return = $value;
        if (strstr($value, 'include')) {
            $res = preg_match('/include\((.*)\)/', $value, $matches);
            if ($res > 0) {
                if (File::Exists(App::$appRoot . '/config/' . $matches[1])) {
                    $return = \yaml_parse_file(App::$appRoot . '/config/' . $matches[1]);
                }
                else if (File::Exists(App::$appRoot . $matches[1])) {
                    $return = \yaml_parse_file(App::$appRoot . $matches[1]);
                }
                else if (File::Exists($matches[1])) {
                    $return = \yaml_parse_file($matches[1]);
                }
                else {
                    $return = null;
                }
            }
            else {
                $return = null;
            }
        }
        return $return;
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

        try {
            $data = $this->_configData;
            foreach ($command as $commandItem) {
                if (strstr($commandItem, '[') !== false) {
                    // массив
                    $res = preg_match('/(.+)\[(\d+)\]/', $commandItem, $matches);
                    if ($res > 0) {
                        $cmdItem = $matches[1];
                        $cmdIndex = $matches[2];
                        $data = $this->_prepareValue($data[$cmdItem][$cmdIndex]);
                    }
                    else {
                        throw new ConfigException('Illeval query: ' . $item);
                    }
                }
                else {
                    if (!isset($data[$commandItem])) {
                        throw new ConfigException('Illeval query: ' . $item);
                    }
                    // не массив
                    $data = $this->_prepareValue($data[$commandItem]);
                }
            }
        }
        catch (ConfigException $e) {
            if ($default) {
                $data = $default;
            }
            else {
                throw $e;
            }
        }

        if (is_array($data) && !$this->isKindOfObject($data)) {
            return new ConfigItemsList($data);
        }
        else {
            return new Config($data);
        }
    }

    /**
     * Вернуть внутренние данные в виде обьекта
     *
     * @return object
     * @testFunction testConfigAsObject
     */
    public function AsObject(): object|string
    {
        if (is_array($this->_configData)) {
            return (object)VariableHelper::ArrayToObject($this->_configData);
        }
        return $this->_configData;
    }

    /**
     * Вернуть внутренние данные в виде массива
     *
     * @return array
     * @testFunction testConfigAsArray
     */
    public function AsArray(): array
    {
        return (array)$this->_configData;
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
    public function isKindOfObject(string|array $param): bool
    {
        $param = (array)$param;
        foreach ($param as $key => $value) {
            if (is_string($key)) {
                return true;
            }
        }

        return false;
    }
}
