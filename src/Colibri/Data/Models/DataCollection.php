<?php

/**
 * Models
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\Models
 */

namespace Colibri\Data\Models;

use ArrayAccess;
use Colibri\App;
use Colibri\Collections\ArrayList;
use Colibri\Data\DataAccessPoint;
use Colibri\Data\NoSqlClient\ICommandResult;
use Colibri\Common\VariableHelper;
use Colibri\Data\SqlClient\QueryInfo;
use Colibri\Utils\ExtendedObject;
use Countable;

/**
 * Represents a data table providing functionalities like counting, array access, and iteration.
 *
 * This class implements Countable, ArrayAccess, and \IteratorAggregate interfaces to provide
 * various data manipulation capabilities.
 */
class DataCollection implements Countable, ArrayAccess, \IteratorAggregate
{
    /**
     * Data access point
     *
     * @var DataAccessPoint
     */
    protected ?DataAccessPoint $_point = null;

    protected ?ICommandResult $_result = null;

    protected array $_rows = [];

    /**
     * Rows class name
     *
     * @var string
     */
    protected ?string $_returnAs = null;

    protected ?string $_table = null;

    /**
     * Constructor
     *
     * @param DataAccessPoint $point
     * @param ?ICommandResult $result
     * @param string $returnAs
     */
    public function __construct(
        DataAccessPoint $point,
        ?ICommandResult $result = null,
        string $returnAs = 'Colibri\\Data\\Models\\DataRow'
    ) {
        $this->_point = $point;
        $this->_result = $result;
        $this->_rows = $result?->ResultData() ?? [];
        $this->_table = $result->QueryInfo()->name;
        $this->_returnAs = $returnAs;
    }

    /**
     * Static constructor
     *
     * @param DataAccessPoint|string $point
     * @param string $returnAs
     * @return DataTable
     */
    public static function Create(
        DataAccessPoint|string $point,
        string $returnAs = 'Colibri\\Data\\Models\\DataRow'
    ): DataTable {
        if (is_string($point)) {
            $point = App::$dataAccessPoints->Get($point);
        }
        return new DataTable($point, null, $returnAs);
    }

    /**
     * Returns iterator
     *
     * @return DataTableIterator
     */
    public function getIterator(): DataTableIterator
    {
        return new DataTableIterator($this);
    }

    /**
     * Executes a query to load data into the DataTable.
     *
     * @param mixed $query The SQL query to execute.
     * @param array $params (optional) An associative array of parameters to bind to the query. Default is an empty array.
     * @return self
     */
    public function Load(mixed $query, array $params = []): self
    {
        $this->_result = $this->_point->ExecuteCommand($query, ...$params);
        $this->_table = $this->_result->QueryInfo()->name;
        return $this;
    }

    /**
     * Gets the number of rows in the DataTable.
     *
     * @return int The number of rows in the DataTable.
     */
    public function Count(): int
    {
        return $this->_result->QueryInfo()->count;
    }

    /**
     * Gets the number of affected rows by the last database operation.
     *
     * @return int|null The number of affected rows, or null if not available.
     */
    public function Affected(): ?int
    {
        return $this->_result->QueryInfo()->affected;
    }

    /**
     * Checks if the DataTable has any rows.
     *
     * @return bool True if the DataTable has rows, false otherwise.
     */
    public function HasRows(): bool
    {
        return $this->_result->QueryInfo()->count > 0;
    }

    /**
     * Retrieves the field names of the DataTable.
     *
     * @return array An array containing the field names of the DataTable.
     */
    public function Fields(): array
    {
        $row = $this->Item(0);
        $fields = array_keys($row ?? []);
        return VariableHelper::ChangeArrayKeyCase($fields, CASE_LOWER);
    }

    /**
     * Retrieves the data access point associated with the DataTable.
     *
     * @return DataAccessPoint|null The data access point associated with the DataTable, or null if not set.
     */
    public function Point(): ?DataAccessPoint
    {
        return $this->_point;
    }

    /**
     * Creates a DataRow object based on the given result.
     *
     * @param mixed $result The result data to create a DataRow object from.
     * @return mixed A DataRow object created from the given result, or null if creation fails.
     */
    protected function _createDataRowObject(mixed $result): mixed
    {
        $className = $this->_returnAs;

        // ищем класс, если нету то добавляем неймспейс App\Models
        if (is_string($className) && !class_exists($className)) {
            $className = 'App\\Models\\' . $className;
            // ищем модель в приложении, если не нашли то берем стандартную модель
            if (!class_exists($className)) {
                $className = 'Colibri\\Data\\Models\\DataRow';
            }
        }

        return new $className($this, $result);
    }

    /**
     * Retrieves an item from the data source at the specified index.
     *
     * @param int $index The index of the item to retrieve.
     * @return mixed The item at the specified index, or null if the index is out of range.
     */
    public function Item(int $index): mixed
    {
        return $this->_createDataRowObject($this->_rows[$index]);
    }

    /**
     * Retrieves the first item from the data table.
     *
     * @return mixed The first item from the data table, or null if the collection is empty.
     */
    public function First(): mixed
    {
        return $this->Item(0);
    }

    /**
     * Creates an empty row object with optional initial data.
     *
     * @param object|array $data (optional) Initial data to populate the row object. Default is an empty array.
     * @return mixed The created empty row object, or null if creation fails.
     */
    public function CreateEmptyRow(object|array $data = []): mixed
    {
        return $this->_createDataRowObject($data);
    }

    /**
     * Saves a DataRow to the data source.
     *
     * @param DataRow $row The DataRow object to be saved.
     * @param string|null $idField (optional) The name of the field representing the primary key. Default is null.
     * @param bool|null $convert (optional) Whether to convert data before saving. Default is true.
     * @return ICommandResult|bool A QueryInfo object containing information about the executed query,
     *                        or boolean true if successful, false otherwise.
     */
    public function SaveRow(DataRow $row, string $idField = 'id', ?bool $convert = true): ICommandResult|bool
    {
        if (!$row->changed) {
            return false;
        }

        $fieldValues = [];
        foreach ($row as $key => $value) {
            if ($row->IsPropertyChanged($key, $convert)) {
                $fieldValues[$key] = $value;
            }
        }

        if ($row->$idField) {
            if (!empty($fieldValues)) {
                $res = $this->_point->ExecuteCommand('UpdateDocument', $this->_table, $fieldValues);
                $queryInfo = $res->QueryInfo();
                if ($queryInfo->affected == 0 && $queryInfo->error) {
                    return $res;
                }
            }
        } else {
            $res = $this->_point->ExecuteCommand('InsertDocument', $this->_table, $fieldValues);
            $queryInfo = $res->QueryInfo();
            if ($queryInfo->affected == 0) {
                return $res;
            }

            $row->$idField = reset($queryInfo->returned);

        }

        $row->UpdateOriginal();

        return true;
    }

    /**
     * Deletes a DataRow from the data source.
     *
     * @param DataRow $row The DataRow object to be deleted.
     * @return QueryInfo A QueryInfo object containing information about the executed delete query.
     */
    public function DeleteRow(DataRow $row, $idField = 'id'): QueryInfo
    {
        if (!$row->$idField) {
            throw new DataModelException('table does not have and autoincrement and can not be saved in standart mode');
        }
        return $this->_point->ExecuteCommand('DeleteDocument', $this->_table, ['id' => $row->$idField]);
    }

    /**
     * Sets an data at the specified index in the data table cache.
     *
     * @param int $index The index at which to set the data.
     * @param ExtendedObject $data The data to set.
     * @return void
     */
    public function Set(int $index, ExtendedObject $data): void
    {
        $this->_rows[$index] = $data;
    }

    /**
     * Converts the data table to an array.
     *
     * @param bool $noPrefix (optional) Whether to exclude the prefix from keys. Default is false.
     * @return array An array representation of the collection.
     */
    public function ToArray(bool $noPrefix = false): array
    {
        $ret = [];
        foreach ($this as $row) {
            $ret[] = $row->ToArray($noPrefix);
        }
        return $ret;
    }

    /**
     * Unplucks a selected fields from table and returns array containing unplucked rows
     *
     * @param array $fields fields to unpluck from row
     * @return array An array representation of the collection.
     */
    public function Unpluck(array $fields): array
    {
        $ret = [];
        foreach ($this as $row) {
            $plucked = [];
            foreach($fields as $field) {
                $plucked[$field] = $row->$field;
            }
            $ret[] = $plucked;
        }
        return $ret;
    }

    /**
     * Saves all DataRow objects in the data table to the data source.
     *
     * @return void
     */
    public function SaveAllRows(): void
    {
        foreach ($this as $row) {
            $this->SaveRow($row, $this->_table);
        }
    }

    /**
     * Deletes all rows from the data source.
     *
     * @return void
     */
    public function DeleteAllRows(): void
    {
        foreach ($this as $row) {
            $this->DeleteRow($row, $this->_table);
        }
    }

    /**
     * Clears the data table, removing all elements.
     *
     * @return void
     */
    public function Clear(): void
    {
        $this->_point->ExecuteCommand('DeleteDocument', $this->_table, []);
    }

    /**
     * Sets a value by index.
     * @param int $offset The index to set the value.
     * @param DataRow $value The value to set.
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->_rows[] = $value;
        } else {
            $this->_rows[$offset] = $value;
        }
    }

    /**
     * Checks if data exists at the specified index.
     * @param int $offset The index to check for data.
     * @return bool True if data exists at the index, false otherwise.
     */
    public function offsetExists(mixed $offset): bool
    {
        return $offset < count($this->_rows);
    }

    /**
     * Removes data at the specified index.
     * @param int $offset The index of the data to remove.
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        array_splice($this->_rows, $offset, 1);
    }

    /**
     * Retrieves the value at the specified index.
     * @param int $offset The index of the value to retrieve.
     * @return DataRow The value at the specified index.
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->Item($offset);
    }

}
