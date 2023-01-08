<?php

/**
 * Serialization
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2020 ColibriLab
 * @package Colibri\Xml\Serialization
 *
 */

namespace Colibri\Xml\Serialization;

/**
 * Класс представление для элемента CDATA
 */
class XmlCData implements \JsonSerializable
{

    /**
     * Значение
     *
     * @var string
     */
    public string $value;

    /**
     * Конструктор
     *
     * @param string $value
     */
    public function __construct(?string $value = null)
    {
        $this->value = $value;
    }

    /**
     * Возвращает данные в виде простого обьекта для упаковки в json
     *
     * @return object
     * @testFunction testJsonSerialize
     */
    public function jsonSerialize(): object|array
    {
        return (object) array('class' => self::class, 'value' => $this->value);
    }
}