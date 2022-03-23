<?php

/**
 * Request
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\IO\Request
 */

namespace Colibri\IO\Request;

use Colibri\Utils\ExtendedObject;

/**
 * Строка данных в запросе
 * @property string $name
 * @property string $value
 * @testFunction testDataItem
 */
class DataItem extends ExtendedObject
{
    /**
     * Конструктор
     *
     * @param string $name название свойства
     * @param string $data данные
     */
    public function __construct($name, $data)
    {
        parent::__construct();
        $this->name = $name;
        $this->value = $data;
    }
}
