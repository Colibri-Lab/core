<?php

namespace Colibri\Web;

use Colibri\Common\XmlHelper;
use ArrayAccess;
use Countable;
use RuntimeException;

class PayloadCopy implements ArrayAccess, Countable
{

    private string $_type;
    private mixed $_payloadData = null;

    public function __construct($type)
    {
        $this->_type = $type;
    }

    private function _loadPayload(): void
    {
        $payload = file_get_contents('php://input');
        if (!$payload) {
            $payload = null;
        }

        if ($this->_type == Request::PAYLOAD_TYPE_JSON) {
            $this->_payloadData = json_decode($payload);
        }
        else if ($this->_type == Request::PAYLOAD_TYPE_XML) {
            $this->_payloadData = XmlHelper::Decode($payload);
        }
    }

    public function __get(string $property): mixed
    {
        if (empty($this->_payloadData)) {
            $this->_loadPayload();
        }
        return isset($this->_payloadData->$property) ? $this->_payloadData->$property : null;
    }


    /**
     * Устанавливает значение по индексу
     * @param int $offset
     * @param mixed $value
     * @return void
     * @testFunction testDataTableOffsetSet
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (empty($this->_payloadData)) {
            $this->_loadPayload();
        }

        if (is_null($offset)) {
            throw new RuntimeException('Error accessing unknown offset');
        }
        else {
            $this->_payloadData->$offset = $value;
        }
    }

    /**
     * Проверяет есть ли данные по индексу
     * @param int $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        if (empty($this->_payloadData)) {
            $this->_loadPayload();
        }
        return isset($this->_payloadData->$offset);
    }

    /**
     * удаляет данные по индексу
     * @param int $offset
     * @return void
     * @testFunction testDataTableOffsetUnset
     */
    public function offsetUnset(mixed $offset): void
    {
        if (empty($this->_payloadData)) {
            $this->_loadPayload();
        }
        unset($this->_payloadData->$offset);
    }

    /**
     * Возвращает значение по индексу
     *
     * @param int $offset
     * @return mixed
     * @testFunction testDataTableOffsetGet
     */
    public function offsetGet(mixed $offset): mixed
    {
        if (empty($this->_payloadData)) {
            $this->_loadPayload();
        }
        return $this->_payloadData->$offset;
    }

    /**
     * Возвращает количество ключей в массиве
     * @return int 
     */
    public function count(): int
    {
        if (empty($this->_payloadData)) {
            $this->_loadPayload();
        }
        return count(array_keys($this->_payloadData));
    }

    public function ToArray(): array
    {
        if (empty($this->_payloadData)) {
            $this->_loadPayload();
        }
        return (array)$this->_payloadData;
    }

}
