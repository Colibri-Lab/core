<?php

/**
 * FileSystem
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\Storages
 */

namespace Colibri\IO\FileSystem;

/**
 * Class for working with directories.
 *
 * Represents a class for handling directories.
 *
 * @property-read Attributes $attributes The attributes of the directory.
 * @property-read array $pathArray The array representation of the directory path.
 * @property-read string $name The name of the directory.
 * @property-read string $path The path of the directory.
 * @property-read boolean $dotfile Indicates if the directory is a dot file.
 * @property-read int $size The size of the directory.
 * @property-read int $current The current directory.
 * @property-read Directory $parent The parent directory.
 * @property-read Security $access The security access of the directory.
 * @property-write boolean $created Indicates if the directory was created.
 * @property-write boolean $midified Indicates if the directory was modified.
 * @property-write boolean $readonly Indicates if the directory is read-only.
 * @property-write boolean $hidden Indicates if the directory is hidden.
 *
 */
class Directory extends Node
{
    /**
     * Path to the folder.
     *
     * Represents the path to the folder.
     *
     * @var string
     */
    private string $path;

    /**
     * Parent directory.
     *
     * Represents the parent directory.
     *
     * @var Directory
     */
    private ? Directory $_parent = null;

    /**
     * Path represented as an array.
     *
     * Represents the path of the folder as an array.
     *
     * @var array|null
     */
    private ?array $_pathArray = null;

    /**
     * Constructor.
     *
     * Initializes a new instance of the Directory class.
     *
     * @param string $path The path to the directory.
     */
    public function __construct(string $path)
    {
        $this->path = dirname($path[strlen($path) - 1] == '/' ? $path . '#' : $path);
    }

    /**
     * Getter.
     *
     * Retrieves the value of a property.
     *
     * @param string $property The property to retrieve.
     * @return mixed The value of the property.
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
                    if (!$this->_pathArray) {
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
                    if (!$this->_parent) {
                        $pathParts = $this->pathArray;
                        unset($pathParts[count($pathParts) - 1]);
                        $this->_parent = new Directory(implode('/', $pathParts) . '/');
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
            case 'access': {
                return $this->getSecurityObject();
            }
            default: {
                    break;
                }
        }
        return $return;
    }

    /**
     * Copies the directory.
     *
     * @param string $path The path where the directory will be copied.
     * @return void
     */
    public function CopyTo(string $path): void
    {
        self::Copy($this->path, $path);
    }

    /**
     * Moves the directory.
     *
     * @param string $path The path where to move the directory.
     * @return void
     */
    public function MoveTo(string $path): void
    {
        self::Move($this->path, $path);
    }

    /**
     * Returns the name of the directory.
     *
     * @return string
     */
    public function ToString(): string
    {
        return $this->path;
    }

    /**
     * Checks if it is a directory.
     *
     * @param string $path
     * @return boolean
     */
    public static function IsDir(string $path): bool
    {
        try {
            return substr($path, strlen($path) - 1, 1) == '/';
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Returns the real path.
     *
     * @param mixed $path The relative path.
     * @return string|bool The real path or false if the path does not exist.
     */
    public static function RealPath(mixed $path): bool|string
    {
        return \realpath($path);
    }

    /**
     * Checks if the directory exists on the disk.
     *
     * @param string $path The path to the directory.
     * @return boolean
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
     * Deletes the directory from the disk.
     *
     * @param string $path The path to the directory.
     * @return void
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
                    } else {
                        unlink($path . '/' . $object);
                    }
                }
            }
            rmdir($path);
        }
    }

    /**
     * Copies the directory.
     *
     * @param string $from The directory to copy from.
     * @param string $to The destination directory.
     * @return void
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
                } else {
                    File::Copy($from . '/' . $file, $to . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    /**
     * Moves the directory.
     *
     * @param string $from The directory to move.
     * @param string $to The destination directory.
     * @return void
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
     * Retrieves information about a directory path.
     *
     * @param string $filename The directory path.
     * @return array An array containing information about the directory.
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
     * Converts the directory object to an array.
     *
     * @return array An array representation of the directory object.
     */
    public function ToArray(): array
    {
        return [
            'name' => $this->name,
            'path' => $this->path . '/',
            'created' => $this->getAttributesObject()->created,
            'modified' => $this->getAttributesObject()->modified,
            'lastaccess' => $this->getAttributesObject()->lastaccess,
            'parent' => ($this->parent->path . '/') ?? null
            /* get directory security */
        ];
    }
}