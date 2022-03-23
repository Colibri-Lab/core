<?php

/**
 * FileSystem
 * 
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\IO\FileSystem
 */

namespace Colibri\IO\FileSystem;

use Colibri\AppException;
use Colibri\Collections\ICollection;

/**
 * Свойства безопасности файловой системы
 * 
 * @property boolean $denied
 * @property boolean $grant
 * @property boolean $read
 * @property boolean $write
 * @property boolean $delete
 * @property boolean $execute
 * @property string $owner
 * 
 * @testFunction testSecurity
 */
class Security
{

    /**
     * Источник
     *
     * @var File|Directory
     */
    protected $source;
    /**
     * Права доступа
     *
     * @var array
     */
    protected $flags;

    /**
     * Конструктор
     *
     * @param File|Directory $source источник
     * @param mixed $flags флаги
     */
    function __construct($source, $flags = null)
    {
        $this->source = $source;
        if ($flags === null) {
            return;
        }

        if ($flags instanceof ICollection) {
            $this->flags = $flags->rawArray;
        } else if (is_array($flags)) {
            $this->flags = $flags;
        } else {
            throw new AppException('illegal arguments: ' . __CLASS__);
        }
    }

    /**
     * Геттер
     *
     * @param string $property свойство
     * @return mixed
     * @testFunction testSecurity__get
     */
    function __get($property)
    {
        return $this->flags[$property];
    }

    /**
     * Сеттер
     *
     * @param string $property свойство
     * @param mixed $value значение
     */
    function __set($property, $value)
    {
        $this->flags[$property] = $value;
    }
}
