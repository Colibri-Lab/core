<?php

/**
 * Fields
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Data\Storages\Fields
 */
namespace Colibri\Data\Storages\Fields;

use Colibri\App;
use Colibri\IO\FileSystem\File;
use Colibri\Graphics\Graphics;
use Colibri\Graphics\Size;
use Colibri\Common\MimeType;
use Colibri\Utils\ExtendedObject;
use Colibri\Data\Storages\Storage;
use JsonSerializable;

/**
 * Представление файла в хранилище
 * @author Vahan P. Grigoryan
 * @package Colibri\Data\Storages\Fields
 *
 * @property-read bool $isOnline да, если файл - это url
 * @property-read bool $isValid да, если файл существует
 * @property-read string $path путь к файлу
 * @property-read MimeType $mimetype Майм тип для файла
 * @property-read string $type тип файла
 * @property-read string $extension тип файла
 * @property-read string $ext тип файла
 * @property-read string $data данные файла
 * @property-read string $binary данные файла
 * @property-read string $content данные файла
 * @property-read Size $size размер изображения, если это графика
 * @property-read string $id название файла, алиас на name
 * @property-read string $name название файла
 * @property-read string $filename название файла, алиас на name
 * @property-read int $filesize размер файла в байтах
 *
 */
class FileField implements JsonSerializable
{
    /**
     * Путь к файлу
     * @var string
     */
    private $_path;

    /**
     * Название файла
     * @var string
     */
    private $_name;

    /**
     * Расширение файла
     * @var string
     */
    private $_ext;

    /**
     * Конрент файла
     * @var string
     */
    private $_content;

    public const JsonSchema = [
        'type' => 'object',
        'patternProperties' => [
            '.*' => [
                'type' => ['number', 'string', 'boolean', 'object', 'array', 'null']
            ]
        ]
    ];

    /**
     * Конструктор
     * @param string $data путь к файлу
     * @return void
     */
    public function __construct($data, ? Storage $storage = null, ? Field $field = null)
    {
        $this->_path = $data;
        $this->_name = basename($this->_path);
        $this->_ext = pathinfo($this->_path, PATHINFO_EXTENSION);
    }

    /**
     * Геттер
     * @param string $nm свойство
     * @return mixed значение
     */
    public function __get($nm)
    {
        switch ($nm) {
            case "isOnline": {
                    return strstr($this->_path, strlen('://')) !== false;
                }
            case "isValid": {
                    if (strstr($this->_path, strlen('://')) !== false) {
                        return true;
                    }
                    if ($this->_path) {
                        return File::Exists(App::$webRoot . $this->_path);
                    }
                    return false;
                }
            case 'path': {
                    return $this->_path;
                }
            case "mimetype": {
                    return new MimeType($this->_ext);
                }
            case "extension":
            case "ext":
            case "type": {
                    return $this->_ext;
                }
            case "binary":
            case "content":
            case "data": {
                    if (is_null($this->_content)) {
                        $this->_content = File::Read(App::$webRoot . $this->_path);
                    }
                    return $this->_content;
                }
            case "size": {
                    if ($this->mimetype->isImage && !$this->isOnline) {
                        if ($this->isValid) {
                            $info = Graphics::Info(App::$webRoot . $this->_path);
                        } else {
                            return new Size();
                        }
                        return $info->size;
                    } else {
                        return null;
                    }
                }
            case "id":
            case "name":
            case "filename": {
                    return $this->_name;
                }
            case "filesize": {
                    if ($this->isOnline) {
                        return 0;
                    }
                    $f = new File(App::$webRoot . $this->_path);
                    return $f->size;
                }
            default: {
                    return null;
                }
        }
    }

    /**
     * Возвращает строку (путь)
     * @return string путь
     */
    public function ToString()
    {
        return $this->_path;
    }

    /**
     * Возвращает наименование для кэширования
     * @param Size $size размер
     * @return string наименование и путь файла кэша
     */
    public function CacheName($size = null)
    {
        if (!$size) {
            $size = new Size(0, 0);
        }
        $md5 = md5($this->_path);
        $subpath = substr($md5, 0, 2) . '/' . substr($md5, 2, 2) . '/';
        $name = md5($this->_path) . "." . $size->width . "x" . $size->height . "." . $this->_ext;
        return App::$config->Query('cache')->GetValue() . 'img/' . $subpath . $name;
    }

    /**
     * Проверяет есть ли уже сохраненных кэш для выбранного размера
     * @param Size $size размер
     * @return bool да, если файл существует
     */
    public function CacheExists($size)
    {
        return File::Exists($this->CacheName($size));
    }

    /**
     * Кэширует файл в нужном размере, если необходимо
     * @param Size|null $size размер
     * @return void
     */
    public function Cache($size = null)
    {
        $cachePath = $this->CacheName($size);

        $data = $this->data;
        if ($this->isValid && $this->mimetype->isImage) {
            if ($size && $size instanceof Size && ($size->width != 0 || $size->height != 0)) {
                $s = $this->size->TransformTo($size);
                $img = Graphics::Create(App::$webRoot . $this->_path);
                $img->Resize($s);
                $data = $img->data;
            }
            File::Write($cachePath, $data, true, '777');
        }
    }

    /**
     * Возвращает путь к файлу с кэшом нужно размера и с нужными свойствами
     * @param Size|null $size размер
     * @param mixed $options Свойства
     * @return string путь к кэшу или к файлу
     */
    public function Source($size = null, $options = null)
    {
        $options = $options ? new ExtendedObject($options) : new ExtendedObject();

        if (!$options->nocache) {
            if ($this->mimetype->isImage && $size) {
                if (!$this->CacheExists($size)) {
                    $this->Cache($size);
                }
                return str_replace(App::$webRoot, '/', $this->CacheName($size));
            } else {
                return str_replace(App::$webRoot, '/', $this->_path);
            }
        } else {
            return str_replace(App::$webRoot, '/', $this->_path);
        }
    }

    /**
     * Return string value of this object
     *
     * @return string
     */
    public function __toString()
    {
        return $this->_path ?: '';
    }

    public function jsonSerialize(): mixed
    {
        return (string) $this;
    }

}