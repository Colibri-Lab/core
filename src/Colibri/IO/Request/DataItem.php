<?php

/**
 * Request
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\Storages
 */

namespace Colibri\IO\Request;

use Colibri\Utils\ExtendedObject;

/**
 * Data string in the request.
 *
 * @property string $name The name of the data string.
 * @property string $value The value of the data string.
 */
class DataItem extends ExtendedObject
{
    /**
     * Constructor.
     *
     * @param string $name The name of the property.
     * @param string $data The data.
     */
    public function __construct(string $name, string $data)
    {
        parent::__construct();
        $this->name = $name;
        $this->value = $data;
    }
}