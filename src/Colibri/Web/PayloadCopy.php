<?php

/**
 * Web
 *
 * This abstract class represents a template for web content generation.
 *
 * @package Colibri\Web
 * @author Vahan P. Grigoryan
 * @copyright 2020 ColibriLab
 */

namespace Colibri\Web;

use Colibri\Common\XmlHelper;
use ArrayAccess;
use Countable;
use RuntimeException;

/**
 * PayloadCopy Class
 *
 * This class represents a copy of payload data received in a web request.
 * It implements the ArrayAccess and Countable interfaces for array-like behavior.
 */
class PayloadCopy implements ArrayAccess, Countable
{
    private string $_type;
    private mixed $_payloadData = null;

    /**
     * Constructor.
     *
     * @param string $type The type of payload data (e.g., json, xml).
     */
    public function __construct($type)
    {
        $this->_type = $type;
    }

    /**
     * Load the payload data.
     *
     * @return void
     */
    private function _loadPayload(): void
    {
        $payload = file_get_contents('php://input');
        if (!$payload) {
            $payload = null;
        }

        if ($payload && $this->_type == Request::PAYLOAD_TYPE_JSON) {
            $this->_payloadData = json_decode($payload);
        } elseif ($payload && $this->_type == Request::PAYLOAD_TYPE_XML) {
            $this->_payloadData = XmlHelper::Decode($payload);
        }
    }

    /**
     * Magic getter method.
     *
     * @param string $property The property name.
     * @return mixed The value of the property.
     */
    public function __get(string $property): mixed
    {
        if (empty($this->_payloadData)) {
            $this->_loadPayload();
        }
        return isset($this->_payloadData->$property) ? $this->_payloadData->$property : null;
    }

    /**
     * Set the value at the specified offset.
     *
     * @param mixed $offset The offset.
     * @param mixed $value The value.
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (empty($this->_payloadData)) {
            $this->_loadPayload();
        }

        if (is_null($offset)) {
            throw new RuntimeException('Error accessing unknown offset');
        } else {
            $this->_payloadData->$offset = $value;
        }
    }

    /**
     * Check if data exists at the specified offset.
     *
     * @param mixed $offset The offset.
     * @return bool True if data exists, false otherwise.
     */
    public function offsetExists(mixed $offset): bool
    {
        if (empty($this->_payloadData)) {
            $this->_loadPayload();
        }
        return isset($this->_payloadData->$offset);
    }

    /**
     * Unset data at the specified offset.
     *
     * @param mixed $offset The offset.
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        if (empty($this->_payloadData)) {
            $this->_loadPayload();
        }
        unset($this->_payloadData->$offset);
    }

    /**
     * Get the value at the specified offset.
     *
     * @param mixed $offset The offset.
     * @return mixed The value at the specified offset.
     */
    public function offsetGet(mixed $offset): mixed
    {
        if (empty($this->_payloadData)) {
            $this->_loadPayload();
        }
        return $this->_payloadData->$offset;
    }

    /**
     * Get the number of keys in the array.
     *
     * @return int The number of keys in the array.
     */
    public function count(): int
    {
        if (empty($this->_payloadData)) {
            $this->_loadPayload();
        }
        return count(array_keys($this->_payloadData));
    }

    /**
     * Convert the payload data to an array.
     *
     * @return array The payload data as an array.
     */
    public function ToArray(): array
    {
        if (empty($this->_payloadData)) {
            $this->_loadPayload();
        }
        return (array) $this->_payloadData;
    }

    public function Cache(): void
    {
        if (empty($this->_payloadData)) {
            $this->_loadPayload();
        }
    }

    public function SetData(array $data) 
    {
        $this->_payloadData = $data;
    }

}
