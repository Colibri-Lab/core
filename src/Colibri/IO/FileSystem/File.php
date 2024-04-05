<?php

/**
 * FileSystem
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\IO\FileSystem
 */

namespace Colibri\IO\FileSystem;

use JsonSerializable;

/**
 * Class for handling files.
 *
 * @property-read Attributes $attributes Attributes of the file.
 * @property-read string $filename Filename.
 * @property-read string $name Name of the file.
 * @property-read string $extension Extension of the file.
 * @property-read Directory $directory Directory containing the file.
 * @property-read boolean $dotfile Indicates if the file is a dot file.
 * @property-read string $path Full path of the file.
 * @property-read int $size Size of the file in bytes.
 * @property-read boolean $exists Indicates if the file exists.
 * @property-read Security $access Access rights of the file.
 * @property-read string $content Content of the file.
 * @property-read string $binary Binary content of the file.
 * @property-read string $mimetype Mime type of the file.
 * @property-read int $attr_created Timestamp of file creation.
 * @property-read int $attr_modified Timestamp of last modification.
 * @property-read int $attr_lastaccess Timestamp of last access.
 *
 */
class File extends Node implements JsonSerializable
{

    /** Read mode */
    const MODE_READ = "rb9";
    /** Write mode */
    const MODE_WRITE = "wb9";
    /** Append mode */
    const MODE_APPEND = "ab9";
    /** Create mode for writing */
    const MODE_CREATEWRITE = "wb9";

    /**
     * File path information.
     *
     * @var array
     */
    private array $info;

    /**
     * Size of the file in bytes.
     *
     * @var integer
     */
    private int $_size = 0;

    /**
     * Constructor.
     *
     * @param string $path The path to the file.
     * @throws Exception if the provided path is not a file path.
     */
    public function __construct(string $path)
    {
        $this->info = Directory::PathInfo($path);
        if ($this->info['basename'] == '') {
            throw new Exception('path argument is not a file path: ' . $path);
        }

        if ($this->info['dirname'] == '.') {
            $this->info['dirname'] = '';
        }
    }

    /**
     * Getter.
     *
     * @param string $property Property name.
     * @return mixed
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
                        $return = strtolower($this->info['extension']);
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
     * Copies the file to another location.
     *
     * @param string $path The destination path.
     * @return void
     */
    public function CopyTo(string $path): void
    {
        self::Copy($this->path, $path);
    }

    /**
     * Moves the file to another location.
     *
     * @param string $path The destination path.
     * @return void
     */
    public function MoveTo(string $path): void
    {
        self::Move($this->path, $path);
    }

    /**
     * Retrieves the filename.
     *
     * @return string
     */
    public function ToString(): string
    {
        return $this->name;
    }

    /**
     * Reads the content of a file.
     *
     * @param string $path The path to the file.
     * @return string|null The content of the file or null if the file does not exist.
     */
    public static function Read(string $path): ?string
    {
        if (self::Exists($path)) {
            return file_get_contents($path);
        }
        return null;
    }

    /**
     * Writes data to a file.
     *
     * @param string $path The path to the file.
     * @param string $content The content to be written.
     * @param boolean $recursive Whether to create directories along the path if they don't exist. Defaults to false.
     * @param string $mode The mode for creating the file and directories. Defaults to '777'.
     * @return void
     * @throws \ErrorException if writing to the file fails.
     */
    public static function Write(string $path, string $content, bool $recursive = false, string $mode = '777'): void
    {
        if (!self::Exists($path)) {
            self::Create($path, $recursive, $mode);
        }

        if(file_put_contents($path, $content) === false) {
            throw new \ErrorException('Can not write to file ' . $path);
        }
    }

    /**
     * Appends data to a file.
     *
     * @param string $path The path to the file.
     * @param string $content The content to be appended.
     * @param boolean $recursive Whether to create directories along the path if they don't exist. Defaults to false.
     * @param string $mode The mode for creating the file and directories. Defaults to '777'.
     * @return void
     */
    public static function Append(string $path, string $content, bool $recursive = false, string $mode = '777'): void
    {
        if (!self::Exists($path)) {
            self::Create($path, $recursive, $mode);
        }

        file_put_contents($path, $content, FILE_APPEND);
    }

    /**
     * Returns a file stream.
     *
     * @param string $path The path to the file.
     * @return FileStream|null Returns FileStream object if file exists, otherwise returns null.
     */
    public static function Open(string $path): ? FileStream
    { //ireader
        if (self::Exists($path)) {
            return new FileStream($path);
        }
        return null;
    }

    /**
     * Checks if a file exists.
     *
     * @param string $path The path to the file.
     * @return bool Returns true if the file exists, false otherwise.
     */
    public static function Exists(string $path): bool
    {
        $path = strval(str_replace("\0", "", $path));
        return file_exists($path);
    }

    /**
     * Checks if a file is empty.
     *
     * @param string $path The path to the file.
     * @return bool Returns true if the file is empty or doesn't exist, false otherwise.
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
     * Creates a file and returns its stream.
     *
     * @param string $path The path to the file.
     * @param bool $recursive If true, creates directories along the path if they don't exist.
     * @param string $mode The mode for creating the file and directories, default is '777'.
     * @return FileStream Returns FileStream object for the created file.
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
     * Deletes a file.
     *
     * @param string $path The path to the file.
     * @return bool Returns true if the file was successfully deleted, false otherwise.
     */
    public static function Delete(string $path): bool
    {
        if (!self::Exists($path)) {
            throw new Exception('file not exists: ' . $path);
        }

        return unlink($path);
    }

    /**
     * Copies a file.
     *
     * @param string $from The source file path.
     * @param string $to The destination file path.
     * @return void
     */
    public static function Copy(string $from, string $to): void
    {
        if (!self::Exists($from)) {
            throw new Exception('file not exists: ' . $from);
        }

        copy($from, $to);
    }

    /**
     * Moves a file.
     *
     * @param string $from The source file path.
     * @param string $to The destination file path.
     * @return void
     */
    public static function Move(string $from, string $to): void
    {
        if (!self::Exists($from)) {
            throw new Exception('source file not exists: ' . $from);
        }

        rename($from, $to);
    }

    /**
     * Checks if the given path is a directory.
     *
     * @param string $path The path to check.
     * @return bool Returns true if the path is a directory, false otherwise.
     */
    public static function IsDirectory(string $path): bool
    {
        return is_dir($path);
    }

    /**
     * Returns file data as an array.
     *
     * @return array Returns an array containing file data.
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

    /**
     * Implements the JsonSerializable interface.
     *
     * @return array Returns the array representation of the object.
     */
    public function jsonSerialize(): array
    {
        return $this->ToArray();
    }

}