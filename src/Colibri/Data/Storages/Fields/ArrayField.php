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
 * Class representing a field of type array of objects
 * @class
 * @extends ArrayList
 */
class ArrayField extends ArrayList
{
    /**
     * Data row associated with this field.
     * @var ExtendedObject|null
     * @protected
     */
    protected ?ExtendedObject $_datarow = null;

    /**
     * Field associated with this array field.
     * @var Field
     * @protected
     */
    protected ?Field $_field = null;

    /**
     * Storage associated with this array field.
     * @var Storage
     * @protected
     */
    protected ?Storage $_storage = null;

    /**
     * Constructs a new ArrayField instance.
     * @param string|mixed[string] $data The data.
     * @param Storage $storage The storage.
     * @param Field $field The field.
     * @return void
     * @constructor
     * @public
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
     * Returns the object at the specified index.
     * @param int $index The index.
     * @return ObjectField The object.
     * @public
     */
    public function Item(int $index): ObjectField|DataRow
    {
        return $this->data[$index] instanceof ObjectField ||
            $this->data[$index] instanceof DataRow ?
                $this->data[$index] :
                new ObjectField($this->data[$index], $this->_storage, $this->_field);
    }

    /**
     * Returns the value as a string.
     * @param string $dummy Not used.
     * @return string The JSON result.
     * @public
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
     * @public
     * @magic
     */
    public function __toString(): string
    {
        return $this->ToString();
    }

    /**
     * Returns the validation data for this array field.
     * @return mixed The validation data.
     * @public
     */
    public function GetValidationData(): mixed
    {
        $return = [];
        foreach ($this as $object) {
            $return[] = $object->GetValidationData();
        }
        return $return;
    }

    /**
     * Converts the array field to an array representation.
     * @param bool $noPrefix Whether to exclude prefixes in the array keys.
     * @return array The array representation of the array field.
     * @public
     */
    public function ToArray(bool $noPrefix = false): array
    {
        $ret = [];
        foreach ($this as $item) {
            $ret[] = $item->ToArray($noPrefix);
        }
        return $ret;
    }

    /**
     * Returns the parameter type name for this array field.
     * @return string The parameter type name.
     * @public
     * @static
     */
    public static function ParamTypeName(): string
    {
        return 'string';
    }

    /**
     * Returns null.
     * @return mixed Always returns null.
     * @public
     * @static
     */
    public static function null(): mixed
    {
        return null;
    }


}
