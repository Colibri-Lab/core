<?php

/**
 * Коллекция данных из запроса
 * Только для чтения
 * 
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Web
 * @version 1.0.0
 * 
 */

namespace Colibri\Web;

use Colibri\Collections\ReadonlyCollection;

/**
 * Коллекция данных из запроса
 * Readonly
 * 
 * Внимание! В целях избавления от проблемы XSS все данные слешируются посредством функции addslashes
 * 
 * @testFunction testRequestCollection
 */
class RequestCollection extends ReadonlyCollection
{

    private bool $_stripSlashes = true;

    public function __construct(mixed $data = array(), bool $stripSlashes = true)
    {
        parent::__construct($data);
        $this->_stripSlashes = $stripSlashes;
        $this->_stripSlashes($data);
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
        if(!$this->_stripSlashes) {
            return $obj;
        }
        
        if (is_array($obj)) {
            foreach ($obj as $k => $v) {
                $obj[$k] = $this->_stripSlashes($v, $strip);
            }
            return $obj;
        }
        else if (is_object($obj)) {
            return $obj;
        }
        else if(is_string($obj)) {
            return $strip ? stripslashes($obj) : addslashes($obj);
        }
        else {
            return $obj;
        }
    }

    /**
     * Магический метод
     *
     * @param string $property
     * @return mixed
     * @testFunction testRequestCollection__get
     */
    public function __get(string $property): mixed
    {
        $val = parent::__get($property);
        return $this->_stripSlashes($val, true);
    }
}
