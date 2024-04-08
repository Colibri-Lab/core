<?php

/**
 * Configuration File Node
 * 
 * Represents a node in a configuration file.
 * 
 * @author Vagan Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Utils\Config
 */
namespace Colibri\Utils\Config;

use Colibri\Collections\ArrayList;

/**
 * Represents a node in a configuration file.
 * 
 * This class extends the ArrayList class and provides functionality to represent a node in a configuration file.
 */
class ConfigItemsList extends ArrayList
{

    private string $_file = '';

    /**
     * Constructor
     * 
     * Initializes a new instance of the ConfigItemsList class.
     *
     * @param array $data The data for the configuration items list.
     * @param string $file The file associated with the configuration items list.
     */
    public function __construct($data = array(), string $file = '')
    {
        parent::__construct($data);
        $this->_file = $file;
    }

    /**
     * Get Item by Index
     * 
     * Retrieves the configuration item at the specified index.
     *
     * @param integer $index The index of the configuration item to retrieve.
     * @return Config The configuration item at the specified index.
     */
    public function Item(int $index): Config
    {
        return new Config($this->data[$index], false, $this->_file);
    }
}