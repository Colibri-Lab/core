<?php

namespace Colibri\Data\Storages\Fields;

use Colibri\Data\Storages\Storage;
use Colibri\Data\Models\DataRow;
use Throwable;
use ReflectionFunction;
use JsonSerializable;
use Closure;

class ClosureField implements JsonSerializable
{

    private string $_value;

    protected ? DataRow $_datarow = null;

    /**
     * Поле
     * @var Field
     */
    protected ? Field $_field = null;

    /**
     * Хранилище
     * @var Storage
     */
    protected ? Storage $_storage = null;

    public function __construct(string $value, ? Storage $storage = null, ? Field $field = null, ? DataRow $datarow = null)
    {
        $this->_value = $value;
        $this->_storage = $storage;
        $this->_field = $field;
        $this->_datarow = $datarow;
    }

    public function __get(string $property): mixed
    {
        if ($property == 'value') {
            return $this->_value;
        }
        return null;
    }

    public function __set(string $property, mixed $value): void
    {
        if ($property == 'value') {
            $this->_value = $value;
        }
    }

    public function Invoke(mixed...$params): mixed
    {
        try {
            eval('$function = ' . $this->_value . ';');
            $function = Closure::fromCallable($function);
            return $function->call($this, ...$params);
        } catch (Throwable $e) {
            return null;
        }

    }

    public function __toString(): string
    {
        return $this->_value;
    }

    public function jsonSerialize(): mixed
    {
        return (string) $this;
    }

}