<?php

namespace Colibri\Data\Storages\Fields;
use JsonSerializable;

class ValueField implements JsonSerializable {

    private $_value;
    private $_title;

    public function __construct($value, $title) {
        $this->_value = $value;
        $this->_title = $title;
    }

    public function __get($property) {
        if($property == 'title') {
            return $this->_title;
        }
        else if($property == 'value') {
            return $this->_value;
        }
        return null;
    }

    public function __set($property, $value) {
        if($property == 'title') {
            $this->_title = $value;
        }
        else if($property == 'value') {
            $this->_value = $value;
        }
    }

    public function __toString() {
        return $this->_value ?: '';
    }

    public function ToArray() {
        return ['title' => $this->_title, 'value' => $this->_value];
    }

    public function jsonSerialize()
    {
        return $this->ToArray();
    }
    

}