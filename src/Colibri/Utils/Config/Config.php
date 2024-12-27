<?php

/**
 * Config
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2020 ColibriLab
 * @package Colibri\Utils\Config
 *
 */

namespace Colibri\Utils\Config;

use Colibri\App;
use Colibri\Common\VariableHelper;
use Colibri\IO\FileSystem\File;
use Colibri\IO\FileSystem\Finder;
use IteratorAggregate;
use Colibri\Collections\ArrayListIterator;

/**
 * Class for working with YAML configuration files.
 *
 */
class Config implements IteratorAggregate
{
    private string $_file = '';

    /**
     * Holds the loaded configuration data.
     *
     * @var mixed
     */
    private $_configData;

    /**
     * Constructor.
     *
     * @param mixed $fileName File or data
     * @param bool $isFile Indicates whether a file was passed or a string
     */
    public function __construct(mixed $fileName, bool $isFile = true, string $file = '')
    {

        $this->_file = $file;

        if (is_array($fileName) || is_object($fileName)) {
            $this->_configData = $fileName;
        } elseif (is_numeric($fileName) || !is_null($fileName)) {
            try {
                if ($isFile && file_exists(App::$appRoot . '/config/' . $fileName)) {
                    $path = App::$appRoot . '/config/' . $fileName;
                    $this->_configData = \yaml_parse_file($path);
                    $this->_file = $fileName;
                } elseif ($isFile && file_exists($fileName)) {
                    $path = $fileName;
                    $this->_configData = \yaml_parse_file($path);
                    $this->_file = $fileName;
                } elseif (VariableHelper::IsBool($fileName)) {
                    $this->_configData = $fileName;
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
     * Load a YAML file.
     *
     * @param string $fileName The name of the YAML file
     * @return Config A Config object representing the loaded YAML file
     */
    public static function LoadFile(string $fileName): Config
    {
        return new Config($fileName);
    }

    /**
     * Load YAML string.
     *
     * @param string $yamlData The YAML data
     * @return Config A Config object representing the loaded YAML string
     */
    public static function Load(string $yamlData): Config
    {
        return new Config($yamlData, false);
    }

    /**
     * Function for processing YAML commands.
     *
     * @param mixed $value The value
     * @return mixed
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
     * Retrieve a value from the configuration.
     *
     * Paths are specified in JavaScript notation.
     * For example: settings.item[0].info or settings.item.buh.notice_email
     *
     * @param string|array $item The path to the value in the configuration file,
     *      if an array is passed, all elements will be sequentially requested until
     *      a positive response is found, if nothing is found, an attempt will be made to return $default
     * @param mixed $default The default value if the path is not found
     * @return ConfigItemsList|Config
     */
    public function Query(string|array $item, mixed $default = null): ConfigItemsList|Config
    {
        if(is_array($item)) {
            $result = null;
            foreach($item as $query) {
                try {
                    $result = $this->Query($query);
                    break;
                } catch(ConfigException $e) {
                    continue;
                }
            }

            if(is_null($result) && $default !== null) {
                $result = $default;
            } elseif ($default === null) {
                throw new ConfigException('Illeval query: ' . implode(';', $item));
            } else {
                return $result;
            }
        }

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
            if ($default !== null) {
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
     * Return the internal data as an object.
     *
     * @return object|string|null
     */
    public function AsObject(): object|string|null
    {
        if (is_array($this->_configData)) {
            return (object) VariableHelper::ArrayToObject($this->_configData);
        }
        return $this->_configData;
    }

    /**
     * Return the internal data as an array.
     *
     * @return array|null
     */
    public function AsArray(): ?array
    {
        return (array) $this->_configData;
    }

    /**
     * Return the stored value.
     * Warning! If the current data is an array or object, null will be returned.
     *
     * @return mixed
     */
    public function GetValue(): mixed
    {
        if ($this->isKindOfObject($this->_configData) || is_array($this->_configData)) {
            return null;
        }
        return $this->_configData;
    }

    /**
     * Checks for the associativeness of the array.
     *
     * @param array|null $param
     * @return bool
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

    /**
     * Save the configuration to a YAML file.
     *
     * @param string $fileName The name of the YAML file
     * @return bool True if the configuration was saved successfully, otherwise false
     * @throws ConfigException If the configuration file cannot be saved
     */
    public function Save(string $fileName = ''): bool
    {
        $fileName = $fileName ?: $this->_file;
        $path = App::$appRoot . '/config/' . $fileName;
        if(!File::Exists($path)) {
            $path = App::$appRoot . $fileName;
        }
        $return = \yaml_emit_file($path, $this->_configData, \YAML_UTF8_ENCODING, \YAML_ANY_BREAK);
        if(!$return) {
            throw new ConfigException('Can not save config file ' . $fileName);
        }
        return true;
    }

    /**
     * Retrieves all configuration files in the /config folder.
     *
     * @return array An array of configuration files
     */
    public static function Enumerate(): array
    {

        $ret = [];
        $finder = new Finder();
        $files = $finder->Files(App::$appRoot . '/config/', '/.yaml/');
        foreach ($files as $file) {
            $ret[] = str_replace(App::$appRoot . '/config/', '', $file->path);
        }
        return $ret;

    }

    /**
     * Get the file name.
     *
     * @return string The file name
     */
    public function GetFile(): string
    {
        return $this->_file;
    }

    /**
     * Set a value.
     *
     * @param string $item The item name
     * @param mixed $value The value
     * @throws ConfigException If an invalid query is made
     */
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

    /**
     * Retrieve an item by index.
     *
     * @param int $index The index of the item
     * @return mixed The item
     */
    public function Item(int $index): mixed
    {
        $keys = array_keys((array)$this->_configData);
        if ($index < count($keys)) {
            return $this->Query($keys[$index]);
        }
        return null;
    }

    /**
     * Get an iterator for the object.
     *
     * @return ArrayListIterator An iterator for the object
     */
    public function getIterator(): ArrayListIterator
    {
        return new ArrayListIterator($this);
    }

}
