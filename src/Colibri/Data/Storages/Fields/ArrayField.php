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
use Colibri\Data\Storages\Models\DataRow;
use Colibri\Utils\ExtendedObject;

/**
 * Класс представление поля типа массив обьектов
 * @author Vahan P. Grigoryan
 * @package Colibri\Data\Storages\Fields
 */
class ArrayField extends ArrayList
{
    protected ?ExtendedObject $_datarow = null;

    /**
     * Поле
     * @var Field
     */
    protected ?Field $_field = null;

    /**
     * Хранилище
     * @var Storage
     */
    protected ?Storage $_storage = null;

    /**
     * Конструктор
     * @param string|mixed[string] $data данные
     * @param Storage $storage хранилище
     * @param Field $field поле
     * @return void
     */
    public function __construct(
        mixed $data,
        ?Storage $storage = null,
        ?Field $field = null,
        ?ExtendedObject $datarow = null
    ) {
        if (VariableHelper::IsNull($data) || VariableHelper::IsEmpty($data)) {
            $data = '[]';
        }
        $data = is_string($data) ? json_decode($data) : $data;
        parent::__construct($data);
        $this->_storage = $storage;
        $this->_field = $field;
        $this->_datarow = $datarow;
    }

    /**
     * Возвращает обьект по индексу
     * @param int $index индекс
     * @return ObjectField обьект
     */
    public function Item(int $index): ObjectField|DataRow
    {
        return $this->data[$index] instanceof ObjectField ||
            $this->data[$index] instanceof DataRow ?
                $this->data[$index] :
                new ObjectField($this->data[$index], $this->_storage, $this->_field);
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
            if (is_object($v) && method_exists($v, 'ToArray')) {
                $obj[] = $v->ToArray();
            } elseif (is_object($v) && method_exists($v, 'ToString')) {
                $obj[] = $v->ToString();
            } else {
                $obj[] = $v;
            }
        }
        return json_encode($obj, JSON_UNESCAPED_UNICODE);
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

    public function GetValidationData(): mixed
    {
        $return = [];
        foreach ($this as $object) {
            $return[] = $object->GetValidationData();
        }
        return $return;
    }

    public function ToArray(bool $noPrefix = false): array
    {
        $ret = [];
        foreach ($this as $item) {
            $ret[] = $item->ToArray($noPrefix);
        }
        return $ret;
    }

}
