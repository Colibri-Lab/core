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
    protected File|Directory|null $source = null;

    /**
     * Список атрибутов
     *
     * @var array
     */
    protected array $attributes = array();

    /**
     * Конструктор
     *
     * @param File $source
     */
    public function __construct(File|Directory $source)
    {
        $this->source = $source;
    }

    /**
     * Геттер
     *
     * @param string $property свойство
     * @return mixed
     */
    public function __get(string $property): mixed
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

                    $return = $this->attributes['created'];
                    break;
                }
            case 'lastaccess': {
                    if (!array_key_exists('created', $this->attributes)) {
                        $this->attributes['created'] = fileatime($this->source->path);
                    }

                    $return = $this->attributes['created'];
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
    public function __set(string $property, mixed $value): void
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
    private function update(string $property, mixed $value): void
    {
        // Do nothing
    }
}
