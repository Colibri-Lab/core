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
    protected File|Directory|null $source = null;
    /**
     * Права доступа
     *
     * @var array
     */
    protected array $flags;

    /**
     * Конструктор
     *
     * @param File|Directory $source источник
     * @param mixed $flags флаги
     */
    function __construct(File|Directory|null $source, mixed $flags = null)
    {
        $this->source = $source;
        if ($flags === null) {
            return;
        }

        if ($flags instanceof ICollection) {
            $this->flags = $flags->{'rawArray'};
        } elseif (is_array($flags)) {
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
    function __get(string $property): mixed
    {
        return $this->flags[$property];
    }

    /**
     * Сеттер
     *
     * @param string $property свойство
     * @param mixed $value значение
     */
    function __set(string $property, mixed $value): void
    {
        $this->flags[$property] = $value;
    }
}