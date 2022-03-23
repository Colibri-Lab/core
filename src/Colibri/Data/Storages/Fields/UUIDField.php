<?php

namespace Colibri\Data\Storages\Fields;

class UUIDField {

    private $_value;

    public function __construct($value, $dummy1 = null, $dummy2 = null) {
        $this->_value = $value;
    }
    public function __get($property) {
        if($property == 'binary') {
            return $this->_value;
        }
        else if($property == 'string') {
            return (string)$this;
        }
        return null;
    }

    public function __set($property, $value) {
        if($property == 'binary') {
            $this->_value = $value;
        }
        else if($property == 'string') {
            $this->_pack($value);
        }
    }

    private function _unpack() {
        $value = unpack("h*", $this->_value);
        return \preg_replace("/([0-9a-f]{8})([0-9a-f]{4})([0-9a-f]{4})([0-9a-f]{4})([0-9a-f]{12})/", "$1-$2-$3-$4-$5", reset($value));
    }

    private function _pack($string) {
        $this->_value = pack("h*", str_replace('-', '', $string));
    }

    public function __toString() {
        return $this->_unpack();
    }

    static function Pack($uuidInString) 
    {
        $uuid = new static(null);
        $uuid->string = $uuidInString;
        return $uuid->binary;
    }

    static function Unpack($uuidInBinary) 
    {
        $uuid = new static(null);
        $uuid->binary = $uuidInBinary;
        return $uuid->string;
    }

}