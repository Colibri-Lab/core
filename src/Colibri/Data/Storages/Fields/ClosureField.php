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
    /**
     * The closure code as a string.
     * @var string
     */
    private string $_value;

    /**
     * The associated data row.
     * @var DataRow|null
     */
    protected ?DataRow $_datarow = null;

    /**
     * The associated field.
     * @var Field
     */
    protected ?Field $_field = null;

    /**
     * The associated storage.
     * @var Storage
     */
    protected ?Storage $_storage = null;

    /**
     * Constructs a new ClosureField instance.
     *
     * @param string $value The closure code as a string.
     * @param Storage|null $storage The associated storage (optional).
     * @param Field|null $field The associated field (optional).
     * @param DataRow|null $datarow The associated data row (optional).
     */
    public function __construct(string $value, ?Storage $storage = null, ?Field $field = null, ?DataRow $datarow = null)
    {
        $this->_value = $value;
        $this->_storage = $storage;
        $this->_field = $field;
        $this->_datarow = $datarow;
    }

    /**
     * Magic getter for properties.
     *
     * @param string $property The property name.
     * @return mixed The property value or null if not found.
     */
    public function __get(string $property): mixed
    {
        if ($property == 'value') {
            return $this->_value;
        }
        return null;
    }

    /**
     * Magic setter for properties.
     *
     * @param string $property The property name.
     * @param mixed $value The value to set.
     * @return void
     */
    public function __set(string $property, mixed $value): void
    {
        if ($property == 'value') {
            $this->_value = $value;
        }
    }

    /**
     * Invokes the closure with the provided parameters.
     *
     * @param mixed ...$params The parameters to pass to the closure.
     * @return mixed The result of the closure execution, or null if an error occurs.
     */
    public function Invoke(mixed...$params): mixed
    {
        try {
            $function = new Closure();
            eval('$function = ' . $this->_value . ';');
            $function = Closure::fromCallable($function);
            return $function->call($this, ...$params);
        } catch (Throwable $e) {
            return null;
        }

    }

    /**
     * Returns the closure code as a string.
     *
     * @return string The closure code.
     */
    public function __toString(): string
    {
        return $this->_value;
    }

    /**
     * Returns the closure code as a string.
     *
     * @return string The closure code.
     */
    public function jsonSerialize(): mixed
    {
        return (string) $this;
    }

    /**
     * Returns the parameter type name for this closure field.
     *
     * @return string The parameter type name.
     */
    public static function ParamTypeName(): string
    {
        return 'string';
    }

    /**
     * Returns null.
     *
     * @return mixed Always returns null.
     */
    public static function null(): mixed
    {
        return null;
    }

}
