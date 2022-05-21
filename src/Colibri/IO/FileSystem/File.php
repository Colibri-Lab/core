<?php

/**
 * FileSystem
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\IO\FileSystem
 */

namespace Colibri\IO\FileSystem;
use JsonSerializable;

/**
 * Класс для работы с файлами
 *
 * @property-read Attributes $attributes
 * @property-read string $filename
 * @property-read string $name
 * @property-read string $extension
 * @property-read Directory $directory
 * @property-read boolean $dotfile
 * @property-read string $path
 * @property-read int $size
 * @property-read boolean $exists
 * @property-read Security $access
 * @property-read string $content
 * @property-write boolean $created
 * @property-write boolean $midified
 * @property-write boolean $readonly
 * @property-write boolean $hidden
 *
 * @testFunction testFile
 */
class File extends Node implements JsonSerializable
{
    
    /** режим чтение */
    const MODE_READ = "rb9";
    /** режим запись */
    const MODE_WRITE = "wb9";
    /** режим добавление данных */
    const MODE_APPEND = "ab9";
    /** режим создания при записи */
    const MODE_CREATEWRITE = "wb9";

    /**
     * Данные о пути к файлу
     *
     * @var array
     */
    private array $info;

    /**
     * Длина файла в байтах
     *
     * @var integer
     */
    private int $_size = 0;

    /**
     * Конструктор
     *
     * @param string $path Путь к файлу
     */
    public function __construct(string $path)
    {
        $this->info = Directory::PathInfo($path);
        if ($this->info['basename'] == '') {
            throw new Exception('path argument is not a file path');
        }

        if ($this->info['dirname'] == '.') {
            $this->info['dirname'] = '';
        }
    }

    /**
     * Геттер
     *
     * @param string $property свойство
     * @return mixed
     * @testFunction testFile__get
     */
    public function __get(string $property): mixed
    {
        $return = null;
        switch (strtolower($property)) {
            case 'attributes': {
                    $return = $this->getAttributesObject();
                    break;
                }
            case 'filename': {
                    $return = $this->info['filename'];
                    break;
                }
            case 'name': {
                    $return = $this->info['basename'];
                    break;
                }
            case 'extension': {
                    if (array_key_exists('extension', $this->info)) {
                        $return = $this->info['extension'];
                    } else {
                        $return = '';
                    }
                    break;
                }
            case 'directory': {
                    if ($this->info['dirname'] !== '') {
                        if (!($this->info['dirname'] instanceof Directory)) {
                            $this->info['dirname'] = new Directory($this->info['dirname'] . '/');
                        }
                        $return = $this->info['dirname'];
                    }
                    break;
                }
            case 'dotfile': {
                    $return = substr($this->name, 0, 1) == '.';
                    break;
                }
            case 'path': {
                    $dirname = $this->info['dirname'] instanceof Directory ? $this->info['dirname']->path : $this->info['dirname'];
                    $return = $dirname . ($dirname ? '/' : '') . $this->info['basename'];
                    break;
                }
            case 'size': {
                    if ($this->_size == 0) {
                        $this->_size = filesize($this->path);
                    }
                    $return = $this->_size;
                    break;
                }
            case 'exists': {
                    $return = self::exists($this->path);
                    break;
                }
            case 'access': {
                    $return = $this->getSecurityObject();
                    break;
                }
            case 'binary':
            case 'content': {
                    if (self::exists($this->path)) {
                        $return = file_get_contents($this->path);
                    }
                    break;
                }
            default: {
                    if (strstr(strtolower($property), 'attr_') !== false) {
                        $p = str_replace('attr_', '', strtolower($property));
                        $return = $this->getAttributesObject()->$p;
                    }
                    break;
                }
        }
        return $return;
    }

    /**
     * Копирует файл
     *
     * @param string $path путь, куда скопировать
     * @return void
     * @testFunction testFileCopyTo
     */
    public function CopyTo(string $path): void
    {
        self::Copy($this->path, $path);
    }

    /**
     * Переместить файл
     *
     * @param string $path путь, куда переместить
     * @return void
     * @testFunction testFileMoveTo
     */
    public function MoveTo(string $path): void
    {
        self::Move($this->path, $path);
    }

    /**
     * Возвращает имя файла
     *
     * @return string
     * @testFunction testFileToString
     */
    public function ToString(): string
    {
        return $this->name;
    }

    /**
     * Считывает данные файл
     *
     * @param string $path путь к файлу
     * @return string|null
     * @testFunction testFileRead
     */
    public static function Read(string $path): ?string
    {
        if (self::Exists($path)) {
            return file_get_contents($path);
        }
        return null;
    }

    /**
     * Записывает данные в файл
     *
     * @param string $path пусть к файлу
     * @param string $content контент, который нужно записать
     * @param boolean $recursive если true то папки будут созданы по всему пути до достижения $path
     * @param integer $mode режим создания файла и папок, по умолчанию 777
     * @return void
     * @testFunction testFileWrite
     */
    public static function Write(string $path, string $content, bool $recursive = false, string $mode = '777'): void
    {
        if (!self::Exists($path)) {
            self::Create($path, $recursive, $mode);
        }

        file_put_contents($path, $content);
    }

    /**
     * Записывает данные в файл
     *
     * @param string $path путь к файлу
     * @param string $content данные, которые нужно дозаписать
     * @param boolean $recursive если true то папки будут созданы по всему пути до достижения $path
     * @param integer $mode режим создания файла и папок, по умолчанию 777
     * @return void
     * @testFunction testFileAppend
     */
    public static function Append(string $path, string $content, bool $recursive = false, string $mode = '777'): void
    {
        if (!self::Exists($path)) {
            self::Create($path, $recursive, $mode);
        }

        file_put_contents($path, $content, FILE_APPEND);
    }

    /**
     * Возвращает стрим файла
     *
     * @param string $path путь к файлу
     * @return FileStream|null
     * @testFunction testFileOpen
     */
    public static function Open(string $path): ?FileStream
    { //ireader
        if (self::Exists($path)) {
            return new FileStream($path);
        }
        return null;
    }

    /**
     * Проверяет наличие файла
     *
     * @param string $path путь к файлу
     * @return boolean
     * @testFunction testFileExists
     */
    public static function Exists(string $path): bool
    {
        $path = strval(str_replace("\0", "", $path));
        return file_exists($path);
    }

    /**
     * Проверяет пустой ли файл
     *
     * @param string $path путь к файлу
     * @return boolean
     * @testFunction testFileIsEmpty
     */
    public static function IsEmpty(string $path): bool
    {
        try {
            $info = stat($path);
            return $info['size'] == 0;
        } catch (Exception $e) {
            return true;
        }
    }

    /**
     * Создает файл и возвращает стрим
     *
     * @param string $path путь к файлу
     * @param boolean $recursive если true то папки будут созданы по всему пути до достижения $path
     * @param string $mode режим создания файла и папок, по умолчанию 777
     * @return FileStream
     * @testFunction testFileCreate
     */
    public static function Create(string $path, bool $recursive = true, string $mode = '777'): FileStream
    {
        if (!Directory::Exists($path) && $recursive) {
            Directory::Create($path, $recursive, $mode);
        }

        if (!self::Exists($path)) {
            touch($path);
        }

        return new FileStream($path);
    }

    /**
     * Удаляет файл
     *
     * @param string $path путь к файлу
     * @return boolean
     * @testFunction testFileDelete
     */
    public static function Delete(string $path): bool
    {
        if (!self::Exists($path)) {
            throw new Exception('file not exists');
        }

        return unlink($path);
    }

    /**
     * Копирует файла
     *
     * @param string $from какой файл
     * @param string $to куда скопировать
     * @return void
     * @testFunction testFileCopy
     */
    public static function Copy(string $from, string $to): void
    {
        if (!self::Exists($from)) {
            throw new Exception('file not exists');
        }

        copy($from, $to);
    }

    /**
     * Переносит файл
     *
     * @param string $from какой файл
     * @param string $to куда перенести
     * @return void
     * @testFunction testFileMove
     */
    public static function Move(string $from, string $to): void
    {
        if (!self::Exists($from)) {
            throw new Exception('source file not exists');
        }

        rename($from, $to);
    }

    /**
     * Проверяет не директория ли
     *
     * @param string $path путь к файлу или директории
     * @return boolean
     * @testFunction testFileIsDirectory
     */
    public static function IsDirectory(string $path): bool
    {
        return is_dir($path);
    }

    /**
     * Возвращает данные в виде массива
     *
     * @return array
     * @testFunction testFileToArray
     */
    public function ToArray(): array
    {
        return array(
            'name' => $this->name,
            'filename' => $this->filename,
            'ext' => $this->extension,
            'path' => $this->path,
            'size' => $this->size,

            'created' => $this->attr_created,
            'modified' => $this->attr_modified,
            'lastaccess' => $this->attr_lastaccess,
        );
    }

    public function jsonSerialize(): array
    {
        return $this->ToArray();
    }

}
