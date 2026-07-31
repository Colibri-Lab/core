<?php

namespace Colibri\Data\Storages\Fields;

use JsonSerializable;

/**
 * Class representing a value in field of storage
 * @class
 * @implements JsonSerializable
 */
class ValueField implements JsonSerializable
{
    /**
     * The value of the field.
     * @var string
     */
    private string $_value;
    /**
     * The title associated with the value.
     * @var string|array|object
     */
    private string|array |object $_title;

    /**
     * Constructs a new ValueField instance.
     *
     * @param string $value The value of the field.
     * @param string|array|object $title The title associated with the value.
     */
    public function __construct(string $value, string|array |object $title)
    {
        $this->_value = $value;
        $this->_title = $title;
    }

    /**
     * Magic getter for properties.
     *
     * @param string $property The property name.
     * @return mixed The property value or null if not found.
     */
    public function __get(string $property): mixed
    {
        if ($property == 'title') {
            return $this->_title;
        } elseif ($property == 'value') {
            return $this->_value;
        }
        return null;
    }

    /**
     * Magic setter for properties.
     *
     * @param string $property The property name.
     * @param mixed $value The value to set.
     * @return void
     */
    public function __set(string $property, mixed $value): void
    {
        if ($property == 'title') {
            $this->_title = $value;
        } elseif ($property == 'value') {
            $this->_value = $value;
        }
    }

    /**
     * Returns the string representation of the value.
     *
     * @return string The string representation of the value.
     */
    public function ToString(): string
    {
        return $this->_value ?: '';
    }

    /**
     * Returns the array representation of the value and title.
     *
     * @return array The array representation containing 'title' and 'value'.
     */
    public function __toString(): string
    {
        return $this->ToString();
    }

    /**
     * Returns the array representation of the value and title.
     *
     * @return array The array representation containing 'title' and 'value'.
     */
    public function ToArray(): array
    {
        return ['title' => $this->_title, 'value' => $this->_value];
    }

    /**
     * Returns the JSON representation of the value and title.
     *
     * @return mixed The JSON representation containing 'title' and 'value'.
     */
    public function jsonSerialize(): string
    {
        return $this->__toString();
    }

    /**
     * Returns the parameter type name for this value field.
     *
     * @return string The parameter type name.
     */
    public static function ParamTypeName(): string
    {
        return 'string';
    }


}
