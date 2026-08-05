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
 * Class representing a file field in a storage system.
 * @class
 * @implements JsonSerializable
 *
 * @property-read bool $isOnline yes, if the file is online (URL)
 * @property-read bool $isValid yes, if the file exists
 * @property-read string $path path to the file
 * @property-read MimeType $mimetype MIME type of the file
 * @property-read string $type file type
 * @property-read string $extension file extension
 * @property-read string $ext file extension
 * @property-read string $data file data
 * @property-read string $binary file data
 * @property-read string $content file data
 * @property-read Size $size image size if the file is an image
 * @property-read string $id file name, alias for name
 * @property-read string $name file name
 * @property-read string $filename file name, alias for name
 * @property-read int $filesize file size in bytes
 *
 */
class FileField implements JsonSerializable
{
    /**
     * Path to the file
     * @var string
     * @private
     */
    private string $_path;

    /**
     * File name
     * @var string
     * @private
     */
    private string $_name;

    /**
     * File extension
     * @var string
     * @private
     */
    private string $_ext;

    /**
     * File content
     * @var string
     * @private
     */
    private string $_content;

    /**
     * JSON schema for the file field
     * @const array
     * @public
     */
    public const JsonSchema = [
        'type' => 'object',
        'patternProperties' => [
            '.*' => [
                'type' => ['number', 'string', 'boolean', 'object', 'array', 'null']
            ]
        ]
    ];

    /**
     * Constructor
     * @param mixed $data The file data, can be a string (path) or an array/object with 'path' key.
     * @param Storage|null $storage The associated storage (optional).
     * @param Field|null $field The associated field (optional).
     * @return void
     * @constructor
     */
    public function __construct($data, ?Storage $storage = null, ?Field $field = null)
    {
        $this->_path = is_array($data) || is_object($data) ? ((array)$data)['path'] : $data;
        $this->_name = basename($this->_path);
        $this->_ext = pathinfo($this->_path, PATHINFO_EXTENSION);
    }

    /**
     * Getter
     * @param string $nm property name
     * @return mixed property value
     * @public
     * @magic
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
     * Returns the string (path)
     * @return string path
     * @public
     */
    public function ToString()
    {
        return $this->_path;
    }

    /**
     * Returns the name for caching
     * @param Size $size size
     * @return string name and path of the cache file
     * @public
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
     * Checks if a cache already exists for the selected size
     * @param Size $size size
     * @return bool true if the cache file exists
     * @public
     */
    public function CacheExists($size)
    {
        return File::Exists($this->CacheName($size));
    }

    /**
     * Caches the file in the required size if necessary
     * @param Size|null $size size
     * @return void
     * @public
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
     * Returns the path to the cached file of the required size and with the required properties
     * @param Size|null $size size
     * @param mixed $options properties
     * @return string path to the cache or the file
     * @public
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
     * @public
     * @magic
     */
    public function __toString()
    {
        return $this->_path ?: '';
    }

    /**
     * Returns the closure code as a string.
     *
     * @return string The closure code.
     * @public
     */
    public function jsonSerialize(): mixed
    {
        return (string) $this;
    }

    /**
     * Returns the field data as an array.
     *
     * @param bool $noPrefix Whether to exclude the prefix from the keys.
     * @return array The field data as an associative array.
     * @public
     */
    public function ToArray(bool $noPrefix = false): array
    {
        return ['path' => $this->_path];
    }

    /**
     * Returns the parameter type name for this file field.
     *
     * @return string The parameter type name.
     * @public
     * @static
     */
    public static function ParamTypeName(): string
    {
        return 'string';
    }

    /**
     * Returns null.
     *
     * @return mixed Always returns null.
     * @public
     * @static
     */
    public static function null(): mixed
    {
        return null;
    }
}
