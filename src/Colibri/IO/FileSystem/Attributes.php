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
 * Аттрибуты файловой системы
 *
 * @property int $created дата создания файла
 * @property int $modified дата последней модификации
 * @property int $lastaccess дата последнего доступа
 *
 */
class Attributes
{
    /**
     * Файл
     *
     * @var File
     */
    protected $source;

    /**
     * Список атрибутов
     *
     * @var array
     */
    protected $attributes = array();

    /**
     * Конструктор
     *
     * @param File $source
     */
    public function __construct($source)
    {
        $this->source = $source;
    }

    /**
     * Геттер
     *
     * @param string $property свойство
     * @return mixed
     */
    public function __get($property)
    {
        $return = null;
        switch ($property) {
            case 'created': {
                    if (!array_key_exists('created', $this->attributes)) {
                        $this->attributes['created'] = filectime($this->source->path);
                    }

                    $return = $this->attributes['created'];
                    break;
                }
            case 'modified': {
                    if (!array_key_exists('created', $this->attributes)) {
                        $this->attributes['created'] = filemtime($this->source->path);
                    }

                    $return =  $this->attributes['created'];
                    break;
                }
            case 'lastaccess': {
                    if (!array_key_exists('created', $this->attributes)) {
                        $this->attributes['created'] = fileatime($this->source->path);
                    }

                    $return =  $this->attributes['created'];
                    break;
                }
            default:
                if (array_key_exists($property, $this->attributes)) {
                    $return = $this->attributes[$property];
                }
        }
        return $return;
    }

    /**
     * Сеттер
     *
     * @param string $property свойство
     * @param mixed $value значение
     */
    public function __set($property, $value)
    {
        if (array_key_exists($property, $this->attributes)) {
            $this->update($property, $value);
        }
    }

    /**
     * Обновляет значение по ключу
     * 
     * @param string $property свойство
     * @param mixed $value значение
     */
    private function update($property, $value)
    {
        //update every time on set new value -> С‚.Рє.
    }
}
