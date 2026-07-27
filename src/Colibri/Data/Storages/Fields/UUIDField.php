<?php

namespace Colibri\Data\Storages\Fields;

use JsonSerializable;

class UUIDField implements JsonSerializable
{
    /**
     * The binary representation of the UUID.
     * @var string
     */
    private string $_value;

    /**
     * Constructs a new UUIDField instance.
     *
     * @param string|null $value The binary representation of the UUID (optional).
     * @param mixed $dummy1 Unused parameter for compatibility (optional).
     * @param mixed $dummy2 Unused parameter for compatibility (optional).
     */
    public function __construct(?string $value = null, mixed $dummy1 = null, mixed $dummy2 = null)
    {
        $this->_value = $value;
    }

    /**
     * Magic getter for properties.
     *
     * @param string $property The property name.
     * @return mixed The property value or null if not found.
     */
    public function __get(string $property): mixed
    {
        if ($property == 'binary') {
            return $this->_value;
        } elseif ($property == 'string') {
            return (string) $this;
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
        if ($property == 'binary') {
            $this->_value = $value;
        } elseif ($property == 'string') {
            $this->_pack($value);
        }
    }

    /**
     * Unpacks the binary UUID into its string representation.
     *
     * @return string The string representation of the UUID.
     */
    private function _unpack(): string
    {
        $value = unpack("h*", $this->_value);
        return \preg_replace("/([0-9a-f]{8})([0-9a-f]{4})([0-9a-f]{4})([0-9a-f]{4})([0-9a-f]{12})/", "$1-$2-$3-$4-$5", reset($value));
    }

    /**
     * Packs the string representation of the UUID into its binary form.
     *
     * @param string $string The string representation of the UUID.
     * @return void
     */
    private function _pack($string): void
    {
        $this->_value = pack("H*", str_replace('-', '', $string));
    }

    /**
     * Returns the string representation of the UUID.
     *
     * @return string The string representation of the UUID.
     */
    public function __toString(): string
    {
        return $this->_unpack();
    }

    /**
     * Packs a string UUID into its binary representation.
     *
     * @param string $uuidInString The string representation of the UUID.
     * @return string The binary representation of the UUID.
     */
    public static function Pack(string $uuidInString): string
    {
        $uuid = new static (null);
        $uuid->string = $uuidInString;
        return $uuid->binary;
    }

    /**
     * Unpacks a binary UUID into its string representation.
     *
     * @param string $uuidInBinary The binary representation of the UUID.
     * @return string The string representation of the UUID.
     */
    public static function Unpack(string $uuidInBinary): string
    {
        $uuid = new static (null);
        $uuid->binary = $uuidInBinary;
        return $uuid->string;
    }

    /**
     * Returns the JSON representation of the UUID.
     *
     * @return mixed The JSON representation of the UUID.
     */
    public function jsonSerialize(): mixed
    {
        return (string) $this;
    }

    /**
     * Returns the parameter type name for this UUID field.
     *
     * @return string The parameter type name.
     */
    public static function ParamTypeName(): string
    {
        return 'string';
    }

    /**
     * Returns null value for the field.
     *
     * @return mixed Always returns null.
     */
    public static function null(): mixed
    {
        return null;
    }

}
