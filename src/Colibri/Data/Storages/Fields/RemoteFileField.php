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
use ReflectionClass;

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
 * @property-read string $data данные файла
 * @property-read Size $size размер изображения, если это графика
 * @property-read string $id название файла, алиас на name
 * @property-read string $name название файла
 * @property-read string $filename название файла, алиас на name
 * @property-read int $filesize размер файла в байтах
 *
 */
class RemoteFileField
{
    /**
     * Данные файла
     * @var string
     */
    private $_data;

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
     * Поле
     * @var Field
     */
    private ?Field $_field = null;

    /**
     * Хранилище
     * @var Storage
     */
    private ?Storage $_storage = null;

    /**
     * Конструктор
     */
    public function __construct(object|string|null $data, ?Storage $storage = null, ?Field $field = null)
    {
        $this->_storage = $storage;
        $this->_field = $field;
        
        if(is_null($data)) {
            $this->_data = [];
            $this->_name = '';
            $this->_ext = '';
        }
        else {
            if(is_string($data)) {
                $data = json_decode($data);
            }
            $this->_data = $data;
            $this->_name = basename($this->_data->name ?? 'untitled.txt');
            $this->_ext = pathinfo($this->_name, PATHINFO_EXTENSION);
        }

    }

    /**
     * Геттер
     * @param string $nm свойство
     * @return mixed значение
     */
    public function __get(string $nm): mixed
    {
        switch ($nm) {
            case "isValid": {
                    return true;
                }
            case 'path': {
                    return $this->_data->name;
                }
            case "mimetype": {
                    return new MimeType($this->_ext);
                }
            case "type": {
                    return $this->_ext;
                }
            case "data": {
                    return $this->_getContent();
                }
            case "filesize":
            case "size": {
                    $this->_data->size;
                }
            case "id":
            case "name":
            case "filename": {
                    return $this->_name;
                }
            default: {
                    return null;
                }
        }
    }

    public function __set(string $nm, mixed $data): void
    {
        if($nm == 'name') {
            $this->_name = $data;
        }
        else if($nm == 'data') {
            $this->_setContent($data);
        }
    }

    private function _getContent(): ?string
    {
        try {

            $params = $this->_field->params;
            $className = $params['class'] ?? null;
            if(!$className) {
                return null;
            }

            $args = $params['args'];
            $method = $params['method'][0];
            $key = $params['key'];

            $reflectionClass = new ReflectionClass($className);
            if(!$reflectionClass->hasMethod($method)) {
                return null;
            }

            $classInstance = $reflectionClass->newInstanceArgs($args);
            return $classInstance->$method($this->_data->$key);
        }
        catch(\Throwable $e) {
            return null;            
        }

    }

    private function _setContent($data): void 
    {
        try {

            $params = $this->_field->params;
            $remote = $params['remote'];
            $className = $remote['class'] ?? null;
            if(!$className) {
                return;
            }

            $args = $remote['args'];
            $method = $remote['method'][1];

            $reflectionClass = new ReflectionClass($className);
            if(!$reflectionClass->hasMethod($method)) {
                return;
            }

            $classInstance = $reflectionClass->newInstanceArgs($args);
            $this->_data = $classInstance->$method($data, $this->_name);
            
        }
        catch(\Throwable $e) {
            return;            
        }
    }

    /**
     * Возвращает строку (путь)
     * @return string путь
     */
    public function ToString(): string
    {
        return json_encode($this->_data);
    }

    public function ConvertFromFile(mixed $file)
    {
        if(!$file) {
            return;
        }
        $this->name = $file->name;
        $this->data = $file->binary;
    }

    /**
     * Возвращает наименование для кэширования
     * @param Size $size размер
     * @return string наименование и путь файла кэша
     */
    public function CacheName(Size $size = null): string
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
    public function CacheExists(Size $size): bool
    {
        return File::Exists($this->CacheName($size));
    }

    /**
     * Кэширует файл в нужном размере, если необходимо
     * @param Size|null $size размер
     * @return void
     */
    public function Cache(?Size $size = null)
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
            File::Write($cachePath, $data, true, 0777);
        }
    }

    /**
     * Возвращает путь к файлу с кэшом нужно размера и с нужными свойствами
     * @param Size|null $size размер
     * @param mixed $options Свойства
     * @return string путь к кэшу или к файлу
     */
    public function Source(?Size $size = null, mixed $options = null): string
    {
        $options = $options ? new ExtendedObject($options) : new ExtendedObject();

        if (!$options->nocache) {
            if ($this->mimetype->isImage && $size) {
                if (!$this->CacheExists($size)) {
                    $this->Cache($size);
                }
                return str_replace(App::$webRoot, '/', $this->CacheName($size));
            }
            else {
                return str_replace(App::$webRoot, '/', $this->_path);
            }
        }
        else {
            return str_replace(App::$webRoot, '/', $this->_path);
        }
    }

    /**
     * Return string value of this object
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->ToString();
    }

    public function ToArray(): array
    {
        return (array)$this->_data;
    }


}
