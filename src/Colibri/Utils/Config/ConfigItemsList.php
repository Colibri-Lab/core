<?php

/**
 * Узел в файле конфигурации
 * 
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Utils\Config
 * @version 1.0.0
 * 
 */

namespace Colibri\Utils\Config;

use Colibri\Collections\ArrayList;

/**
 * Узел в файле конфигурации
 * @testFunction testConfigItemsList
 */
class ConfigItemsList extends ArrayList
{

    private string $_file = '';

    public function __construct($data = array(), string $file = '') {
        parent::__construct($data);
        $this->_file = $file;
    }

    /**
     * Возвращает значение по идексу
     *
     * @param integer $index
     * @return Config
     * @testFunction testConfigItemsListItem
     */
    public function Item(int $index): Config
    {
        return new Config($this->data[$index], false, $this->_file);
    }
}
