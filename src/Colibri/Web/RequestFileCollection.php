<?php

/**
 * Коллекция файлов запроса
 * Только для чтения
 * 
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Web
 * @version 1.0.0
 * 
 */

namespace Colibri\Web;

/**
 * Коллекция файлов запроса
 * Readonly
 * @testFunction testRequestFileCollection
 */
class RequestFileCollection extends RequestCollection
{

    public function __construct(mixed $data = array(), bool $stripSlashes = true)
    {
        // $data [key => value]
        // value может быть массивом упакованным в поля
        $add = [];
        foreach ($data as $key => $value) {
            if (is_array($value['name'])) {
                $add[$key] = [];
                for ($i = 0; $i < count($value['name']); $i++) {
                    $add[$key][] = (object) [
                        'name' => $value['name'][$i],
                        'type' => $value['type'][$i],
                        'tmp_name' => $value['tmp_name'][$i],
                        'error' => $value['error'][$i],
                        'size' => $value['size'][$i]
                    ];
                }
            } else {
                $add[$key] = (object) $value;
            }
        }
        parent::__construct($add, $stripSlashes);
    }

    /**
     * Чистит или добавляет слэши в значения
     *
     * @param string|string[] $obj
     * @return string|string[]
     * @testFunction testRequestCollection_stripSlashes
     */
    protected function _stripSlashes(mixed $obj, bool $strip = false): mixed
    {
        return $obj;
    }

    public function Item(mixed $key): mixed
    {
        if ($this->Exists($key)) {
            if (is_array($this->data[$key])) {
                $ret = [];
                foreach ($this->data[$key] as $file) {
                    $ret[] = new RequestedFile($file);
                }
                return $ret;
            }

            return new RequestedFile($this->data[$key]);
        }
        return null;
    }

    public function ItemAt(int $index): mixed
    {
        $key = $this->Key($index);
        if (!$key) {
            return null;
        }

        if (is_array($this->data[$key])) {
            $ret = [];
            foreach ($this->data[$key] as $file) {
                $ret[] = new RequestedFile($file);
            }
            return $ret;
        }

        return new RequestedFile($this->data[$key]);
    }
}