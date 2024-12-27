<?php

/**
 * PgSql
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\PgSql
 */

namespace Colibri\Data\PgSql;

use Colibri\Data\SqlClient\DataField;
use Colibri\Data\SqlClient\IDataReader;
use Throwable;
use resource;

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
            pg_free_result($this->_results);
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
        $result = pg_fetch_object($this->_results);
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
        $num = pg_num_fields($this->_results);
        for ($i = 0; $i < $num; $i++) {

            // pg_field_is_null — Проверка поля на значение SQL NULL
            // pg_field_name — Возвращает наименование поля
            // pg_field_num — Возвращает порядковый номер именованного поля
            // pg_field_prtlen — Возвращает количество печатаемых символов
            // pg_field_size — Возвращает размер поля
            // pg_field_table — Возвращает наименование или идентификатор таблицы, содержащей заданное поле
            // pg_field_type_oid — Возвращает идентификатор типа заданного поля
            // pg_field_type — Возвращает и

            $field = new DataField();
            $field->db = pg_dbname($this->_results);
            $field->name = pg_field_name($this->_results, $i);
            $field->originalName = pg_field_name($this->_results, $i);
            $field->table = pg_field_table($this->_results, $i);
            $field->originalTable = $field->table;
            // $field->def = $f->def;
            $field->maxLength = pg_field_prtlen($this->_results, $i);
            $field->length = pg_field_size($this->_results, $i);
            // $field->decimals = $f->decimals;
            // $field->type = $this->_type2txt(pg_field_type($this->_results, $i));
            // $field->flags = $this->_flags2txt($f->flags);
            $field->escaped = '`' . $field->originalTable . '`.`' . $field->originalName . '`';
            $fields[pg_field_name($this->_results, $i)] = $field;

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
                $return = $this->_results && pg_num_rows($this->_results) > 0;
                break;
            }
            case 'affected': {
                $return = $this->_affected;
                break;
            }
            case 'count': {
                if (is_null($this->_count)) {
                    $this->_count = pg_num_rows($this->_results);
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
     * Converts the PostgreSQL field type ID to a readable string.
     *
     * @param string $type_id The PostgreSQL field type ID.
     * @return string|null The readable string representing the field type.
     */
    private function _type2txt(string $type_id): string
    {
        static $types;

        if (!isset($types)) {
            self::$types = [];
            $constants = get_defined_constants(true);
            foreach ($constants['pgsql'] as $c => $n) {
                if (preg_match('/^PGSQL_(.*)/', $c, $m)) {
                    $types[$n] = $m[1];
                }
            }
        }

        return array_key_exists($type_id, $types) ? $types[$type_id] : null;
    }

    /**
     * Converts the PostgreSql field flags to a readable string.
     *
     * @param int $flags_num The PostgreSQL field flags.
     * @return array An array containing the readable field flags.
     */
    private function _flags2txt(int $flags_num): array
    {
        static $flags;

        if (!isset($flags)) {
            $flags = [];
            $constants = get_defined_constants(true);
            foreach ($constants['pgsql'] as $c => $n) {
                if (preg_match('/^PGSQL_(.*)_FLAG$/', $c, $m) && !array_key_exists($n, self::$flags)) {
                    $flags[$n] = $m[1];
                }
            }
        }

        $result = [];
        foreach ($flags as $n => $t) {
            if ($flags_num & $n) {
                $result[] = $t;
            }
        }
        return $result;
    }
}
