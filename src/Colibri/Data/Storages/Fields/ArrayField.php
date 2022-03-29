<?php

/**
 * Fields
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Data\Storages\Fields
 */
namespace Colibri\Data\Storages\Fields;

use Colibri\Collections\ArrayList;
use Colibri\Common\VariableHelper;
use Colibri\Data\Storages\Storage;

/**
 * Класс представление поля типа массив обьектов
 * @author Vahan P. Grigoryan
 * @package Colibri\Data\Storages\Fields
 */
class ArrayField extends ArrayList
{
    /**
     * Конструктор
     * @param string|mixed[string] $data данные
     * @param Storage $storage хранилище
     * @param Field $field поле
     * @return void
     */
    public function __construct(mixed $data, ?Storage $storage = null, ?Field $field = null)
    {
        if (VariableHelper::IsNull($data) || VariableHelper::IsEmpty($data)) {
            $data = '[]';
        }
        $data = is_string($data) ? json_decode($data) : $data;
        parent::__construct($data);
        $this->_storage = $storage;
        $this->_field = $field;
        $this->_prefix = '';
    }

    /**
     * Возвращает обьект по индексу
     * @param int $index индекс
     * @return ObjectField обьект
     */
    public function Item(int $index): ObjectField
    {
        return $this->data[$index] instanceof ObjectField ? $this->data[$index] : new ObjectField($this->data[$index], $this->_storage, $this->_field);
    }

    /**
     * Возвращает значение в виде строки
     * @param string $dummy не используется
     * @return string результат JSON
     */
    public function ToString(string $dummy = ''): string
    {
        $obj = array();
        if (VariableHelper::IsNull($this->data)) {
            $this->data = array();
        }
        foreach ($this->data as $v) {
            if (is_object($v) && method_exists($v, 'ToString')) {
                $obj[] = $v->ToString();
            }
            else {
                $obj[] = $v;
            }
        }
        return json_encode($obj);
    }

    /**
     * Return string value of this object
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->ToString();
    }

}
