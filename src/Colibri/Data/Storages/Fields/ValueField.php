<?php

namespace Colibri\Data\Storages\Fields;
use JsonSerializable;

class ValueField implements JsonSerializable
{

    private string $_value;
    private string $_title;

    public function __construct(string $value, string $title)
    {
        $this->_value = $value;
        $this->_title = $title;
    }

    public function __get(string $property): mixed
    {
        if ($property == 'title') {
            return $this->_title;
        }
        else if ($property == 'value') {
            return $this->_value;
        }
        return null;
    }

    public function __set(string $property, mixed $value): void
    {
        if ($property == 'title') {
            $this->_title = $value;
        }
        else if ($property == 'value') {
            $this->_value = $value;
        }
    }

    public function __toString(): string
    {
        return $this->_value ?: '';
    }

    public function ToArray(): array
    {
        return ['title' => $this->_title, 'value' => $this->_value];
    }

    public function jsonSerialize(): string
    {
        return $this->ToArray();
    }



}