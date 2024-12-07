<?php


/**
 * MsSql
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\MsSql
 */
namespace Colibri\Data\MsSql;

use Colibri\Data\SqlClient\DataField;
use Colibri\Data\SqlClient\IDataReader;

/**
 * Class responsible for working with query results.
 * 
 * @property-read bool $hasRows Indicates whether the result set has any rows.
 * @property int $affected Number of affected rows.
 * @property-read int $count Number of rows in the result set.
 * 
 */
final class DataReader implements IDataReader
{
    /**
     * Query result resource.
     *
     * @var mixed
     */
    private mixed $_results = null;

    /**
     * Number of results in the current query page.
     *
     * @var int
     */
    private ?int $_count = null;

    /**
     * Total number of results.
     * Filled only when the query is executed with the info parameter set to true in ExecuteReader.
     *
     * @var int
     */
    private ?int $_affected = null;

    /**
     * Query string after processing.
     * @var string|null
     */
    private ?string $_preparedQuery = null;

    /**
     * Creates a new object.
     *
     * @param mixed $results Query results.
     * @param int|null $affected Number of affected rows.
     * @param string|null $preparedQuery Processed query string.
     */
    public function __construct(mixed $results, ?int $affected = null, ?string $preparedQuery = null)
    {
        $this->_results = $results;
        $this->_affected = $affected;
        $this->_preparedQuery = $preparedQuery;
    }

    /**
     * Destructor to close the resource.
     */
    public function __destruct()
    {
        $this->Close();
    }

    /**
     * Closes the query result resource.
     *
     * @return void
     */
    public function Close(): void
    {
        if ($this->_results && isset($this->_results->current_field)) {
            \sqlsrv_free_stmt($this->_results);
            $this->_results = null;
        }
    }

    /**
     * Reads the next row from the query result.
     *
     * @return object|null The next row as an object, or null if no more rows are available.
     */
    public function Read(): ?object
    {
        $result = \sqlsrv_fetch_object($this->_results);
        if (!$result) {
            return null;
        }

        return $result;
    }

    /**
     * Retrieves the list of fields in the query result.
     *
     * @return array The list of fields in the query result.
     */
    public function Fields(): array
    {
        $fields = array();
        $num = \sqlsrv_num_fields($this->_results);
        $fieldMeta = \sqlsrv_field_metadata($this->_results);
        for ($i = 0; $i < $num; $i++) {
            $f = $fieldMeta[$i];
            $field = new DataField();
            $field->db = $f->db;
            $field->name = $f->name;
            $field->originalName = $f->orgname;
            $field->table = $f->table;
            $field->originalTable = $f->orgtable;
            $field->def = $f->def;
            $field->maxLength = $f->max_length;
            $field->length = $f->length;
            $field->decimals = $f->decimals;
            $field->type = $this->_type2txt($f->type);
            $field->flags = $this->_flags2txt($f->flags);
            $field->escaped = '`' . $field->originalTable . '`.`' . $field->originalName . '`';
            $fields[$f->name] = $field;
        }
        return $fields;
    }

    /**
     * Magic getter method to retrieve properties.
     *
     * @param string $property The property name.
     * @return mixed The value of the property.
     */
    public function __get(string $property): mixed
    {
        $return = null;
        $property = strtolower($property);
        switch ($property) {
            case 'hasrows': {
                    $return = $this->_results && sqlsrv_num_rows($this->_results) > 0;
                    break;
                }
            case 'affected': {
                    $return = $this->_affected;
                    break;
                }
            case 'count': {
                    if (is_null($this->_count)) {
                        $this->_count = sqlsrv_num_rows($this->_results);
                    }
                    $return = $this->_count;
                    break;
                }
            default:
                $return = null;
        }
        return $return;
    }

    /**
     * Magic setter method.
     *
     * @param string $property The property name.
     * @param mixed $value The value to set.
     * @return void
     */
    public function __set(string $property, mixed $value): void
    {
        if (strtolower($property) == 'affected') {
            $this->_affected = $value;
        }
    }
    /**
     * Returns the number of rows in the result set.
     *
     * @return int The number of rows in the result set.
     */
    public function Count(): int
    {
        return $this->count;
    }

    /**
     * Converts the MsSql field type ID to a readable string.
     *
     * @param string $type_id The MsSql field type ID.
     * @return string|null The readable string representing the field type.
     */
    private function _type2txt(string $type_id): string
    {
        static $types;

        if (!isset($types)) {
            $types = array();
            $constants = get_defined_constants(true);
            foreach ($constants['SQLSRV'] as $c => $n) {
                if (preg_match('/^SQLSRV_TYPE_(.*)/', $c, $m)) {
                    $types[$n] = $m[1];
                }
            }
        }

        return array_key_exists($type_id, $types) ? $types[$type_id] : null;
    }

    /**
     * Converts the MsSql field flags to a readable string.
     *
     * @param int $flags_num The MsSql field flags.
     * @return array An array containing the readable field flags.
     */
    private function _flags2txt(int $flags_num): array
    {
        static $flags;

        if (!isset($flags)) {
            $flags = array();
            $constants = get_defined_constants(true);
            foreach ($constants['SQLSRV'] as $c => $n) {
                if (preg_match('/SQLSRV_(.*)_FLAG$/', $c, $m) && !array_key_exists($n, $flags)) {
                    $flags[$n] = $m[1];
                }
            }
        }

        $result = array();
        foreach ($flags as $n => $t) {
            if ($flags_num & $n) {
                $result[] = $t;
            }
        }
        return $result;
    }
}