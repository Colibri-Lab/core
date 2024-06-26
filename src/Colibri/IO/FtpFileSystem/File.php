<?php

/**
 * FtpFileSystem
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\IO\FtpFileSystem
 */

namespace Colibri\IO\FtpFileSystem;

use Colibri\App;
use JsonSerializable;
use Colibri\IO\FileSystem\File as BaseFile;

/**
 * Class for remote file operations.
 *
 * This class provides functionalities to interact with remote files via FTP.
 *
 * @property-read string $attributes The file attributes.
 * @property-read string $filename The file name.
 * @property-read string $name The base name of the file.
 * @property-read string $extension The extension of the file.
 * @property-read mixed $directory The directory containing the file.
 * @property-read bool $dotfile Indicates if the file is a dot file.
 * @property-read string $path The full path to the file.
 * @property-read int $size The size of the file in bytes.
 * @property-read bool $exists Indicates if the file exists.
 * @property-read mixed $access The access permissions of the file.
 * @property-read string $content The content of the file.
 * @property-read string $binary The binary content of the file.
 * @property-read string $mimetype The MIME type of the file.
 */
class File implements JsonSerializable
{
    /** Read mode */
    public const MODE_READ = "rb9";
    /** Write mode */
    public const MODE_WRITE = "wb9";
    /** Append mode */
    public const MODE_APPEND = "ab9";
    /** Create mode */
    public const MODE_CREATEWRITE = "wb9";

    /**
     * File path information.
     *
     * @var array
     */
    private array $info;

    /**
     * The file item.
     *
     * @var object
     */
    private object $item;

    /**
     * The FTP connection.
     *
     * @var mixed
     */
    private mixed $connection;

    /**
     * The size of the file in bytes.
     *
     * @var int
     */
    private int $_size = 0;

    /**
     * The cache path for downloaded files.
     *
     * @var string
     */
    private string $cachePath;

    /**
     * The file finder.
     *
     * @var mixed
     */
    private mixed $finder;

    /**
     * Constructor.
     *
     * Initializes a new instance of the File class.
     *
     * @param object $item The file item.
     * @param mixed $connection The FTP connection.
     * @param mixed $finder The file finder.
     */
    public function __construct(object $item, mixed $connection, mixed $finder)
    {
        $this->finder = $finder;
        $this->cachePath = App::$appRoot . App::$config->Query('runtime')->GetValue();
        $this->connection = $connection;
        $this->item = $item;
        $this->info = pathinfo($item->name);
        if ($this->info['basename'] == '') {
            throw new Exception('path argument is not a file path');
        }

        if ($this->info['dirname'] == '.') {
            $this->info['dirname'] = '';
        }
    }

    /**
     * Magic method for property access.
     *
     * Provides read-only access to various file properties.
     *
     * @param string $property The property to access.
     * @return mixed The value of the accessed property.
     */
    public function __get(string $property): mixed
    {
        $return = null;
        switch (strtolower($property)) {
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
                $return = $this->item->size;
                break;
            }
            case 'access': {
                $return = $this->item->perm;
                break;
            }
            case 'binary':
            case 'content': {
                if($this->Download($this->cachePath . $this->name)) {
                    $return = BaseFile::Read($this->cachePath . $this->name);
                    BaseFile::Delete($this->cachePath . $this->name);
                }
                break;
            }

        }
        return $return;
    }

    /**
     * Download the file.
     *
     * Downloads the remote file to the specified local path.
     *
     * @param string $localPath The local path to save the file.
     * @return bool Returns true if the download is successful, false otherwise.
     */
    public function Download($localPath): bool
    {
        try {

            if(!ftp_get($this->connection, $localPath, $this->item->name)) {
                throw new Exception('Can not download file');
            }

        } catch(\Throwable $e) {
            $this->connection = $this->finder->Reconnect();
            if(!ftp_get($this->connection, $localPath, $this->item->name)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Converts file data to an array.
     *
     * Converts various file properties to an associative array.
     *
     * @return array An array representation of the file.
     */
    public function ToArray(): array
    {
        return array(
            'name' => $this->name,
            'filename' => $this->name,
            'ext' => $this->extension,
            'path' => $this->path,
            'size' => $this->size,
        );
    }

    /**
     * Implements the JsonSerializable interface.
     *
     * Serializes the file object to JSON.
     *
     * @return array An array representation of the file for JSON serialization.
     */
    public function jsonSerialize(): array
    {
        return $this->ToArray();
    }

}
