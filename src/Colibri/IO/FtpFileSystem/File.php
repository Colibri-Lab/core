<?php

/**
 * FileSystem
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\IO\FileSystem
 */

namespace Colibri\IO\FtpFileSystem;

use Colibri\App;
use JsonSerializable;
use Colibri\IO\FileSystem\File as BaseFile;

/**
 * Класс для работы с файлами
 *
 * @property-read string $attributes
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
 *
 * @testFunction testFile
 */
class File implements JsonSerializable
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

    private object $item;

    private \FTP\Connection $connection;

    /**
     * Длина файла в байтах
     *
     * @var integer
     */
    private int $_size = 0;

    private string $cachePath;

    /**
     * Конструктор
     *
     * @param string $path Путь к файлу
     */
    public function __construct(object $item, \FTP\Connection $connection)
    {
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
                    $return = $this->item->size;
                    break;
                }
            case 'access': {
                    $return = $this->item->perm;
                    break;
                }
            case 'binary':
            case 'content': {
                    if(ftp_get($this->connection, $this->cachePath . $this->name, $this->item->name)) {
                        $return = BaseFile::Read($this->cachePath . $this->name);
                        BaseFile::Delete($this->cachePath . $this->name);
                    }
                    break;
                }

        }
        return $return;
    }

    public function Download($localPath): bool
    {
        if(ftp_get($this->connection, $localPath, $this->item->name)) {
            return false;
        }
        return true;
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
            'filename' => $this->name,
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