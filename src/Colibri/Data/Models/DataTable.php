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
use Colibri\Common\Encoding;
use Colibri\Data\DataAccessPoint;
use Colibri\Data\SqlClient\IDataReader;
use Colibri\Common\VariableHelper;
use Colibri\Data\SqlClient\QueryInfo;
use Colibri\Utils\ExtendedObject;
use Countable;
use Colibri\Collections\IArrayList;
use Colibri\Data\NoSqlClient\ICommandResult;

/**
 * Represents a data table providing functionalities like counting, array access, and iteration.
 *
 * This class implements Countable, ArrayAccess, and \IteratorAggregate interfaces to provide
 * various data manipulation capabilities.
 * 
 * @class
 * @implements Countable
 * @implements ArrayAccess
 * @implements \IteratorAggregate
 * @example
 * ```
 * $dataTable = DataTable::Create($accessPoint, 'User');
 * $dataTable->Load('SELECT * FROM users'); // Load the data into the table
 * $count = $dataTable->Count(); // Get the number of rows in the table
 * $firstRow = $dataTable->First(); // Get the first row from the table
 * ```
 */
class DataTable implements Countable, ArrayAccess, \IteratorAggregate
{

    /**
     * Data access point
     *
     * @var DataAccessPoint
     * @protected
     */
    protected ?DataAccessPoint $_point = null;

    /**
     * DataReader
     *
     * @var IDataReader
     * @protected
     */
    protected ?IDataReader $_reader = null;

    /**
     * List of loaded rows
     *
     * @var ArrayList
     * @protected
     */
    protected ?ArrayList $_cache = null;

    /**
     * Rows class name
     *
     * @var string
     * @protected
     */
    protected ?string $_returnAs = null;

    /**
     * Constructor
     *
     * @param DataAccessPoint $point
     * @param IDataReader $reader
     * @param string $returnAs
     * @constructor
     * @public
     */
    public function __construct(
        DataAccessPoint $point,
        ?IDataReader $reader = null,
        string $returnAs = 'Colibri\\Data\\Models\\DataRow'
    ) {
        $this->_point = $point;
        $this->_reader = $reader;
        $this->_cache = new ArrayList();
        $this->_returnAs = $returnAs;
    }

    /**
     * Static constructor
     *
     * @param DataAccessPoint|string $point
     * @param string $returnAs
     * @return DataTable
     * @public
     * @static
     * @example
     * ```
     * $dataTable = DataTable::Create($accessPoint, 'User'); // Load the data into the table
     * $dataTable->Load('SELECT * FROM users'); // Load the data into the table
     * ```
     */
    public static function Create(
        DataAccessPoint|string $point,
        string $returnAs = 'Colibri\\Data\\Models\\DataRow'
    ): DataTable {
        if (\is_string($point)) {
            $point = App::$dataAccessPoints->Get($point);
        }
        return new DataTable($point, null, $returnAs);
    }

    /**
     * Returns iterator
     *
     * @return DataTableIterator
     * @public
     * @example
     * ```
     * $dataTable = DataTable::Create($accessPoint, 'User');
     * $dataTable->Load('SELECT * FROM users'); // Load the data into the table
     * foreach ($dataTable as $row) { // Iterate through each row in the data table
     *     /// Process each row
     * }
     * ```
     */
    public function getIterator(): DataTableIterator
    {
        return new DataTableIterator($this);
    }

    /**
     * Executes a query to load data into the DataTable.
     *
     * @param string $query The SQL query to execute.
     * @param array $params (optional) An associative array of parameters to bind to the query. Default is an empty array.
     * @return self
     * @public
     * @example
     * ```
     * $dataTable = DataTable::Create($accessPoint, 'User');
     * $dataTable->Load('SELECT * FROM users WHERE status = [[status:string]]', ['status' => 'active']); // Load active users into the data table
     * ```
     */
    public function Load(string $query, array $params = []): self
    {
        $params = (object) $params;

        if ($this->_cache) {
            $this->_cache->Clear();
        }

        if ($this->_reader) {
            $this->_reader->Close();
        }

        if (strstr(strtolower($query), 'select') === false) {
            // если нет слова select, то это видимо название таблицы, надо проверить
            if (strstr($query, ' ') !== false) {
                // есть пробел, значит это не название таблицы нужно выдать ошибку
                throw new DataModelException('Param query can be only the table name or select query');
            } else {
                $query = 'select * from ' . $query;
                $condition = [];
                if (isset($params->params)) {
                    foreach ($params->params as $key => $value) {
                        $condition[] = $key . '=[[' . $key . ']]';
                    }
                }
                if (!empty($condition)) {
                    $query .= ' where ' . implode(' and ', $condition);
                }
            }
        }

        $this->_reader = $this->_point->Query($query, $params);
        return $this;
    }

    /**
     * Gets the number of rows in the DataTable.
     *
     * @return int The number of rows in the DataTable.
     * @public
     * @example
     * ```
     * $dataTable = DataTable::Create($accessPoint, 'User');
     * $dataTable->Load('SELECT * FROM users'); // Load the data into the table
     * $rowCount = $dataTable->Count(); // Get the number of rows in the data table
     * ```
     */
    public function Count(): int
    {
        return $this->_reader?->Count() ?? 0;
    }

    /**
     * Gets the number of affected rows by the last database operation.
     *
     * @return int|null The number of affected rows, or null if not available.
     * @public
     * @example
     * ```
     * $dataTable = DataTable::Create($accessPoint, 'User');
     * $dataTable->Load('SELECT * FROM users'); // Load the data into the table
     * $affectedRows = $dataTable->Affected(); // Get the number of affected rows by the last operation
     * ```
     */
    public function Affected(): ?int
    {
        return $this->_reader?->{'affected'} ?? 0;
    }

    /**
     * Checks if the DataTable has any rows.
     *
     * @return bool True if the DataTable has rows, false otherwise.
     * @public
     * @example
     * ```
     * $dataTable = DataTable::Create($accessPoint, 'User');
     * $dataTable->Load('SELECT * FROM users'); // Load the data into the table
     * $hasRows = $dataTable->HasRows(); // Check if the data table has any rows
     * ```
     */
    public function HasRows(): bool
    {
        return $this->_reader?->{'hasrows'} ?? false;
    }

    /**
     * Retrieves the field names of the DataTable.
     *
     * @return array An array containing the field names of the DataTable.
     * @public
     * @example
     * ```
     * $dataTable = DataTable::Create($accessPoint, 'User');
     * $dataTable->Load('SELECT * FROM users'); // Load the data into the table
     * $fields = $dataTable->Fields(); // Get the field names of the data table
     * ```
     */
    public function Fields(): array
    {
        return VariableHelper::ChangeArrayKeyCase($this->_reader->Fields(), CASE_LOWER);
    }

    /**
     * Retrieves the data access point associated with the DataTable.
     *
     * @return DataAccessPoint|null The data access point associated with the DataTable, or null if not set.
     * @public
     * @example
     * ```
     * $dataTable = DataTable::Create($accessPoint, 'User');
     * $dataTable->Load('SELECT * FROM users'); // Load the data into the table
     * $point = $dataTable->Point(); // Get the data access point associated with the data table
     * ```
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
     * @protected
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
     * Reads data from a data source.
     *
     * @return mixed The data read from the data source, or null if reading fails.
     * @protected
     */
    protected function _read(): mixed
    {
        return $this->_createDataRowObject(
            $this->_reader->Read()
        );
    }

    /**
     * Reads data from a data source up to a specified index.
     *
     * @param int $index The index up to which data should be read.
     * @return mixed The data read from the data source up to the specified index, or null if reading fails.
     * @protected
     */
    protected function _readTo(int $index): mixed
    {
        while ($this->_cache->Count() < $index) {
            $this->_cache->Add($this->_read());
        }
        return $this->_cache->Add($this->_read());
    }

    /**
     * Retrieves an item from the data source at the specified index.
     *
     * @param int $index The index of the item to retrieve.
     * @return mixed The item at the specified index, or null if the index is out of range.
     * @public
     * @example 
     * ```
     * $dataTable = DataTable::Create($accessPoint, 'User');
     * $dataTable->Load('SELECT * FROM users'); // Load the data into the table
     * $firstItem = $dataTable->Item(0); // Get the first item from the data table
     * ```
     */
    public function Item(int $index): mixed
    {
        if ($index >= $this->_cache->Count()) {
            return $this->_readTo($index);
        } else {
            return $this->_cache->Item($index);
        }
    }

    /**
     * Retrieves the first item from the data table.
     *
     * @return mixed The first item from the data table, or null if the collection is empty.
     * @public
     * @example
     * ```
     * $dataTable = DataTable::Create($accessPoint, 'User');
     * $dataTable->Load('SELECT * FROM users'); // Load the data into the table
     * $firstItem = $dataTable->First(); // Get the first item from the data table
     * ```
     */
    public function First(): mixed
    {
        return $this->Item(0);
    }

    /**
     * Caches all data from the data source.
     *
     * @param bool $closeReader (optional) Whether to close the reader after caching. Default is true.
     * @return mixed The cached data, or null if caching fails.
     * @public
     * @example
     * ```
     * $dataTable = DataTable::Create($accessPoint, 'User');
     * $dataTable->Load('SELECT * FROM users'); // Load the data into the table
     * $cachedData = $dataTable->CacheAll(); // Cache all data from the data table
     * ```
     */
    public function CacheAll(bool $closeReader = true): mixed
    {
        $this->_readTo($this->Count() - 1);
        if ($closeReader) {
            $this->_reader->Close();
        }
        return $this->_cache;
    }

    /**
     * Creates an empty row object with optional initial data.
     *
     * @param object|array $data (optional) Initial data to populate the row object. Default is an empty array.
     * @return mixed The created empty row object, or null if creation fails.
     * @public
     * @example
     * ```
     * $dataTable = DataTable::Create($accessPoint, 'User');
     * $dataTable->Load('SELECT * FROM users'); // Load the data into the table
     * $emptyRow = $dataTable->CreateEmptyRow(); // Create an empty row object
     * ```
     */
    public function CreateEmptyRow(object|array $data = []): mixed
    {
        return $this->_createDataRowObject($data);
    }

    /**
     * Retrieves the encoding information for a specified table.
     *
     * @param string $table The name of the table to retrieve encoding information for.
     * @return object An object containing encoding information for the specified table.
     * @private
     */
    private function _getTableEncoding(string $table): object
    {
        // получаем кодировку таблицы
        $table = explode('.', $table);
        $reader = $this->_point->Query('show table status in ' . $table[0] . ' like \'' . $table[1] . '\'');
        $status = $reader->Read();
        $collation = $status->Collation;

        // получаем что то типа <encoding>_<collation type>
        $parts = explode('_', $collation);
        return (object) ['encoding' => reset($parts), 'collation' => $collation];
    }

    /**
     * Saves a DataRow to the data source.
     *
     * @param DataRow $row The DataRow object to be saved.
     * @param string|null $idField (optional) The name of the field representing the primary key. Default is null.
     * @param bool|null $convert (optional) Whether to convert data before saving. Default is true.
     * @return QueryInfo|bool A QueryInfo object containing information about the executed query,
     *                        or boolean true if successful, false otherwise.
     * @public
     * @example
     * ```
     * $dataTable = DataTable::Create($accessPoint, 'User');
     * $dataTable->Load('SELECT * FROM users'); // Load the data into the table
     * $firstItem = $dataTable->First(); // Get the first item from the data table
     * $dataTable->SaveRow($firstItem); // Save the first item back to the data table
     * ```
     */
    public function SaveRow(DataRow $row, ?string $idField = null, ?bool $convert = true): QueryInfo|bool
    {
        if (!$row->changed) {
            return false;
        }

        $tables = [];
        $idFields = [];
        $notNullFields = [];
        $fields = $this->Fields();
        foreach ($fields as $field) {
            if (in_array('PRI_KEY', $field->flags)) {
                $idFields[] = strtolower($field->name);
            }
            if (in_array('NOT_NULL', $field->flags)) {
                $notNullFields[] = strtolower($field->name);
            }
            $table = (isset($field->originalTable) ? $field->originalTable : $field->table);
            if ($table) {
                $tables[$field->db . '.' . $table] = $field->db . '.' . $table;
            }
        }

        $table = reset($tables);

        if ($idField && empty($idFields)) {
            $idFields[] = $idField;
        }

        if (empty($idFields)) {
            throw new DataModelException('table does not have and autoincrement and can not be saved in standart mode');
        }

        $encoding = $this->_getTableEncoding($table);

        // устанавливаем кодировку клиента
        $this->_point->Query(
            'set names ' . $encoding->encoding,
            (object) ['type' => DataAccessPoint::QueryTypeNonInfo]
        );

        $fieldValues = [];
        foreach ($row as $key => $value) {
            if ($row->IsPropertyChanged($key, $convert)) {
                $fieldValues[$key] = $encoding->encoding != Encoding::UTF8 && $convert ?
                    Encoding::Convert((string) $value, $encoding->encoding) : $value;
            }
        }

        $isFilled = false;
        foreach ($idFields as $field) {
            if ($row->{$field}) {
                $isFilled = true;
            }
        }

        if ($isFilled) {
            if (!empty($fieldValues)) {
                // есть обновляем
                $flt = [];
                foreach ($idFields as $field) {
                    $flt[] = $field . '=\'' . $row->{$field} . '\'';
                }

                $res = $this->_point->Update($table, $fieldValues, implode(' and ', $flt));
                if ($res->affected == 0 && $res->error) {
                    return $res;
                }
            }
        } else {
            $res = $this->_point->Insert($table, $fieldValues);
            if ($res->affected == 0) {
                return $res;
            }

            // если это ID то сохраняем
            if (count($idFields) == 1) {
                foreach ($idFields as $f) {
                    $row->$f = $res->insertid;
                }
            }

        }

        $row->UpdateOriginal();

        return true;
    }

    /**
     * Deletes a DataRow from the data source.
     *
     * @param DataRow $row The DataRow object to be deleted.
     * @return QueryInfo A QueryInfo object containing information about the executed delete query.
     * @public
     * @example
     * ```
     * $dataTable = DataTable::Create($accessPoint, 'User');
     * $dataTable->Load('SELECT * FROM users'); // Load the data into the table
     * $firstItem = $dataTable->First(); // Get the first item from the data table
     * $dataTable->DeleteRow($firstItem); // Delete the first item from the data table
     * ```
     */
    public function DeleteRow(DataRow $row): QueryInfo|ICommandResult|bool
    {
        $tables = [];
        $idFields = [];
        $fields = $row->properties;
        foreach ($fields as $field) {
            if (in_array('PRI_KEY', $field->flags)) {
                $idFields[] = $field;
            }
            $table = (isset($field->originalTable) ? $field->originalTable : $field->table);
            if ($table) {
                $tables[$table] = $table;
            }
        }

        if (count($tables) != 1) {
            throw new DataModelException('Can not find any table name to use in save operation');
        }
        $table = reset($tables);

        if (empty($idFields)) {
            throw new DataModelException('table does not have and autoincrement and can not be saved in standart mode');
        }

        $condition = [];
        foreach ($idFields as $f) {
            $condition[] = $f->escaped . '=\'' . $row->{$f->name} . '\'';
        }

        return $this->_point->Delete($table, implode(' and ', $condition));
    }

    /**
     * Sets an data at the specified index in the data table cache.
     *
     * @param int $index The index at which to set the data.
     * @param ExtendedObject $data The data to set.
     * @return void
     * @public
     * @example
     * ```
     * $dataTable = DataTable::Create($accessPoint, 'User');
     * $dataTable->Load('SELECT * FROM users'); // Load the data into the table
     * $newData = new ExtendedObject(['name' => 'John Doe', 'email' => 'john.doe@example.com']);
     * $dataTable->Set(0, $newData); // Set the new data at the specified index
     * ```
     */
    public function Set(int $index, ExtendedObject $data): void
    {
        $this->_cache->Set($index, $data);
    }

    /**
     * Converts the data table to an array.
     *
     * @param bool $noPrefix (optional) Whether to exclude the prefix from keys. Default is false.
     * @return array An array representation of the collection.
     * @public
     * @example
     * ```
     * $dataTable = DataTable::Create($accessPoint, 'User');
     * $dataTable->Load('SELECT * FROM users'); // Load the data into the table
     * $arrayRepresentation = $dataTable->ToArray(); // Convert the data table to an array
     * ```
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
     * @public
     * @example
     * ```
     * $dataTable = DataTable::Create($accessPoint, 'User');
     * $dataTable->Load('SELECT * FROM users'); // Load the data into the table
     * $unpluckedData = $dataTable->Unpluck(['name', 'email']); // Unpluck the specified fields from the data table
     * ```
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
     * @public
     * @example
     * ```
     * $dataTable = DataTable::Create($accessPoint, 'User');
     * $dataTable->Load('SELECT * FROM users'); // Load the data into the table
     * $dataTable->SaveAllRows(); // Save all rows in the data table
     * ```
     */
    public function SaveAllRows(): void
    {
        foreach ($this as $row) {
            $this->SaveRow($row);
        }
    }

    /**
     * Deletes all rows from the data source.
     *
     * @return void
     * @public
     * @example
     * ```
     * $dataTable = DataTable::Create($accessPoint, 'User');
     * $dataTable->Load('SELECT * FROM users'); // Load the data into the table
     * $dataTable->DeleteAllRows(); // Delete all rows from the data table
     * ```
     */
    public function DeleteAllRows(): void
    {
        foreach ($this as $row) {
            $this->DeleteRow($row);
        }
    }

    /**
     * Clears the data table, removing all elements.
     *
     * @return void
     * @public
     * @example
     * ```
     * $dataTable = DataTable::Create($accessPoint, 'User');
     * $dataTable->Load('SELECT * FROM users'); // Load the data into the table
     * $dataTable->Clear(); // Clear the data table, removing all elements
     * ```
     */
    public function Clear(): void
    {
        $tables = [];
        $fields = $this->Fields();
        foreach ($fields as $field) {
            $table = (isset($field->originalTable) ? $field->originalTable : $field->table);
            if ($table) {
                $tables[$field->db . '.' . $table] = $field->db . '.' . $table;
            }
        }

        $table = reset($tables);

        $this->_point->Delete($table, ''); // используется truncate
    }

    /**
     * Sets a value by index.
     * @param int $offset The index to set the value.
     * @param DataRow $value The value to set.
     * @return void
     * @public
     * @example
     * ```
     * $dataTable = DataTable::Create($accessPoint, 'User');
     * $dataTable->Load('SELECT * FROM users'); // Load the data into the table
     * $newData = new DataRow($dataTable, ['name' => 'John Doe', 'email' => 'john.doe@example.com']);
     * $dataTable[0] = $newData; // Add the new data row to the data table
     * ```
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (is_null($offset)) {
            $this->_cache->Add($value);
        } else {
            $this->_cache->Set($offset, $value);
        }
    }

    /**
     * Checks if data exists at the specified index.
     * @param int $offset The index to check for data.
     * @return bool True if data exists at the index, false otherwise.
     * @public
     * @example
     * ```
     * $dataTable = DataTable::Create($accessPoint, 'User');
     * $dataTable->Load('SELECT * FROM users'); // Load the data into the table
     * $exists = isset($dataTable[0]); // Check if data exists at the specified index
     * ```
     */
    public function offsetExists(mixed $offset): bool
    {
        return $offset < $this->_cache->Count();
    }

    /**
     * Removes data at the specified index.
     * @param int $offset The index of the data to remove.
     * @return void
     * @public
     * @example
     * ```
     * $dataTable = DataTable::Create($accessPoint, 'User');
     * $dataTable->Load('SELECT * FROM users'); // Load the data into the table
     * unset($dataTable[0]); // Remove data at the specified index from the data table
     * ```
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->_cache->DeleteAt($offset);
    }

    /**
     * Retrieves the value at the specified index.
     * @param int $offset The index of the value to retrieve.
     * @return DataRow The value at the specified index.
     * @public
     * @example
     * ```
     * $dataTable = DataTable::Create($accessPoint, 'User');
     * $dataTable->Load('SELECT * FROM users'); // Load the data into the table
     * $value = $dataTable[0]; // Retrieve the value at the specified index from the data table
     * ```  
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->Item($offset);
    }

}
