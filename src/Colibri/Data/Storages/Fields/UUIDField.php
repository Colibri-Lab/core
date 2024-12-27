<?php

namespace Colibri\Data\Storages\Fields;

use JsonSerializable;

class UUIDField implements JsonSerializable
{
    private string $_value;

    public function __construct(?string $value = null, mixed $dummy1 = null, mixed $dummy2 = null)
    {
        $this->_value = $value;
    }

    public function __get(string $property): mixed
    {
        if ($property == 'binary') {
            return $this->_value;
        } elseif ($property == 'string') {
            return (string) $this;
        }
        return null;
    }

    public function __set(string $property, mixed $value): void
    {
        if ($property == 'binary') {
            $this->_value = $value;
        } elseif ($property == 'string') {
            $this->_pack($value);
        }
    }

    private function _unpack(): string
    {
        $value = unpack("h*", $this->_value);
        return \preg_replace("/([0-9a-f]{8})([0-9a-f]{4})([0-9a-f]{4})([0-9a-f]{4})([0-9a-f]{12})/", "$1-$2-$3-$4-$5", reset($value));
    }

    private function _pack($string): void
    {
        $this->_value = pack("H*", str_replace('-', '', $string));
    }

    public function __toString(): string
    {
        return $this->_unpack();
    }

    public static function Pack(string $uuidInString): string
    {
        $uuid = new static (null);
        $uuid->string = $uuidInString;
        return $uuid->binary;
    }

    public static function Unpack(string $uuidInBinary): string
    {
        $uuid = new static (null);
        $uuid->binary = $uuidInBinary;
        return $uuid->string;
    }

    public function jsonSerialize(): mixed
    {
        return (string) $this;
    }

    public static function ParamTypeName(): string
    {
        return 'string';
    }

    public static function null(): mixed
    {
        return null;
    }

}
