<?php

/**
 * FileSystem
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\IO\FileSystem
 */

namespace Colibri\IO\FileSystem;

/**
 * Класс для работы с директориями
 *
 * @property-read Attributes $attributes
 * @property-read string $name
 * @property-read string $path
 * @property-read boolean $dotfile
 * @property-read int $size
 * @property-read Directory $parent
 * @property-read Security $access
 * @property-write boolean $created
 * @property-write boolean $midified
 * @property-write boolean $readonly
 * @property-write boolean $hidden
 *
 * @testFunction testDirectory
 */
class Directory extends Node
{
    /**
     * Путь к папке
     *
     * @var string
     */
    private string $path;

    /**
     * Родительская директория
     *
     * @var Directory
     */
    private ?Directory $_parent = null;

    /**
     * Путь в виде массива  
     *
     * @var array
     */
    private ?array $_pathArray = null;

    /**
     * Конструктор
     *
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->path = dirname($path[strlen($path) - 1] == '/' ? $path . '#' : $path);
    }

    /**
     * Геттер
     *
     * @param string $property свойство
     * @return mixed
     * @testFunction testDirectory__get
     */
    public function __get(string $property): mixed
    {
        $return = null;
        switch (strtolower($property)) {
            case 'current':
            case 'size': {
                    $return = null;
                    break;
                }
            case 'attributes': {
                    $return = $this->getAttributesObject();
                    break;
                }
            case 'patharray': {
                    if(!$this->_pathArray) {
                        $this->_pathArray = explode('/', $this->path);
                    }
                    $return = $this->_pathArray; 
                    break;
                }
            case 'name': {
                    $return = $this->pathArray[count($this->pathArray) - 1];
                    break;
                }
            case 'parent': {
                    if(!$this->_parent) {                        
                        $pathParts = $this->pathArray;
                        unset($pathParts[count($pathParts) - 1]);
                        $this->_parent = new Directory(implode('/', $pathParts).'/');
                    }
                    $return = $this->_parent;
                    break;
                }
            case 'path': {
                    $return = $this->path . '/';
                    break;
                }
            case 'dotfile': {
                    $return = substr($this->name, 0, 1) == '.';
                    break;
                }
            case 'access':
                return $this->getSecurityObject();
            default: {
                    break;
                }
        }
        return $return;
    }

    /**
     * Копирует директорию
     *
     * @param string $path путь куда скопировать директорию
     * @return void
     * @testFunction testDirectoryCopyTo
     */
    public function CopyTo(string $path): void
    {
        self::Copy($this->path, $path);
    }

    /**
     * Перемещает директорию
     *
     * @param string $path путь куда переместить директорию
     * @return void
     * @testFunction testDirectoryMoveTo
     */
    public function MoveTo(string $path): void
    {
        self::Move($this->path, $path);
    }

    /**
     * Возвращает наименование директории
     *
     * @return string
     * @testFunction testDirectoryToString
     */
    public function ToString(): string
    {
        return $this->path;
    }

    /**
     * Проверяет директория ли
     *
     * @param string $path
     * @return boolean
     * @testFunction testDirectoryIsDir
     */
    public static function IsDir(string $path): bool
    {
        try {
            return substr($path, strlen($path) - 1, 1) == '/';
        }
        catch (Exception $e) {
            return false;
        }
    }

    /**
     * Возвращает реальный путь
     * @param mixed $path относительный путь
     * @return string|string 
     * @testFunction testDirectoryRealPath
     */
    public static function RealPath(mixed $path): bool|string
    {
        return \realpath($path);
    }

    /**
     * Проверяет есть ли директория на диске
     *
     * @param string $path путь к директории
     * @return boolean
     * @testFunction testDirectoryExists
     */
    public static function Exists(string $path): bool
    {
        return File::Exists(dirname($path[strlen($path) - 1] == '/' ? $path . '#' : $path));
    }

    /**
     * Создает директорию
     *
     * @param string $path пусть к директории
     * @param boolean $recursive если true то директории будут созданы по всему пути до достижения указанной директории
     * @param string $mode режим создания по умолчанию 777
     * @return Directory
     * @testFunction testDirectoryCreate
     */
    public static function Create(string $path, bool $recursive = true, string $mode = '777'): Directory
    {
        if (!self::Exists($path)) {
            $path2 = dirname($path[strlen($path) - 1] == '/' ? $path . '#' : $path);
            shell_exec('mkdir' . ($recursive ? ' -p' : '') . ' -m' . $mode . ' ' . $path2);
        }

        return new self($path);
    }

    /**
     * Удаляет директорию с диска
     *
     * @param string $path путь к директории
     * @return void
     * @testFunction testDirectoryDelete
     */
    public static function Delete(string $path): void
    {
        if (!self::Exists($path)) {
            throw new Exception('directory not exists');
        }

        if (is_dir($path)) {
            $objects = scandir($path);
            foreach ($objects as $object) {
                if ($object != '.' && $object != '..') {
                    if (is_dir($path . "/" . $object)) {
                        self::Delete($path . '/' . $object);
                    }
                    else {
                        unlink($path . '/' . $object);
                    }
                }
            }
            rmdir($path);
        }
    }

    /**
     * Копирует директорию
     *
     * @param string $from какую директорию скопировать
     * @param string $to куда скопировать
     * @return void
     * @testFunction testDirectoryCopy
     */
    public static function Copy(string $from, string $to): void
    {
        if (!self::Exists($from)) {
            throw new Exception('source directory not exists');
        }

        if (!self::Exists($to)) {
            self::Create($to, true, 0766);
        }

        $dir = opendir($from);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($from . '/' . $file)) {
                    self::Copy($from . '/' . $file . '/', $to . '/' . $file . '/');
                }
                else {
                    File::Copy($from . '/' . $file, $to . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    /**
     * Перемещает директорию
     *
     * @param string $from какую директорию переместить
     * @param string $to куда переместить
     * @return void
     * @testFunction testDirectoryMove
     */
    public static function Move(string $from, string $to): void
    {
        if (!self::exists($from)) {
            throw new Exception('source directory not exists');
        }
        if (self::exists($to)) {
            throw new Exception('target directory exists');
        }

        rename($from, $to);
    }

    /**
     * Возвращает обьект содержащий данные о директории
     *
     * @param string $filename
     * @return array
     * @testFunction testDirectoryPathInfo
     */
    public static function PathInfo(string $filename): array
    {
        $pathInfo = [];
        $pathInfo['dirname'] = dirname($filename);
        $pathInfo['basename'] = trim(substr($filename, strlen($pathInfo['dirname']) + 1), '/');
        $parts = explode('.', $pathInfo['basename']);
        $pathInfo['extension'] = end($parts);
        $pathInfo['filename'] = substr($pathInfo['basename'], 0, -1 * strlen('.' . $pathInfo['extension']));
        return $pathInfo;
    }

    /**
     * Возвращает массив из данных
     *
     * @return array
     * @testFunction testDirectoryToArray
     */
    public function ToArray(): array
    {
        return [
            'name' => $this->name,
            'path' => $this->path . '/',
            'created' => $this->getAttributesObject()->created,
            'modified' => $this->getAttributesObject()->modified,
            'lastaccess' => $this->getAttributesObject()->lastaccess,
            'parent' => ($this->parent->path.'/') ?? null
            /* get directory security */
        ];
    }
}
