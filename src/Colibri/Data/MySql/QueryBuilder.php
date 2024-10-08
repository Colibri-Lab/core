<?php


/**
 * MySql
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\MySql
 */
namespace Colibri\Data\MySql;

use Colibri\Common\StringHelper;
use Colibri\Data\SqlClient\IQueryBuilder;

/**
 * Class for generating queries for the MySql driver.
 *
 * This class implements the IQueryBuilder interface, providing methods to generate various types of SQL queries
 * compatible with the MySql database.
 *
 */
class QueryBuilder implements IQueryBuilder
{
    /**
     * Creates an INSERT query.
     *
     * @param string $table The name of the table.
     * @param array|object $data The data to insert.
     * @param string $returning (optional) The returning clause for the query. Default is empty string.
     * @return string The generated INSERT query.
     */
    public function CreateInsert(string $table, array |object $data, string $returning = ''): string
    {
        $data = (array) $data;
        foreach ($data as $key => $value) {
            if (is_null($value)) {
                $value = 'null';
            } elseif (is_bool($value)) {
                $value = '\'' . ($value ? 1 : 0) . '\'';
            } elseif (StringHelper::IsJsonString($value) || (strstr($value, '[[') === false || strstr($value, ']]') === false)) {
                $value = '\'' . addslashes($value) . '\'';
            }
            $data[$key] = $value;
        }

        $keys = array_keys($data);
        $fields = '(`' . join("`, `", $keys) . '`)';

        $vals = array_values($data);
        $values = "(" . join(", ", $vals) . ")";

        return "insert into `" . $table . '`' . $fields . ' values' . $values;
    }

    /**
     * Creates a REPLACE INTO query.
     *
     * @param string $table The name of the table.
     * @param array|object $data The data to replace.
     * @param string $returning (optional) The returning clause for the query. Default is empty string.
     * @return string The generated REPLACE INTO query.
     */
    public function CreateReplace(string $table, array |object $data, string $returning = ''): string
    {
        $data = (array) $data;
        foreach ($data as $key => $value) {
            if (is_null($value)) {
                $value = 'null';
            } elseif (is_bool($value)) {
                $value = '\'' . ($value ? 1 : 0) . '\'';
            } elseif (strstr($value, '[[') === false || strstr($value, ']]') === false) {
                $value = '\'' . addslashes($value) . '\'';
            }
            $data[$key] = $value;
        }

        $keys = array_keys($data);
        $fields = '(`' . join("`, `", $keys) . '`)';

        $vals = array_values($data);
        $values = "(" . join(", ", $vals) . ")";

        return "replace into `" . $table . '`' . $fields . ' values' . $values;
    }

    /**
     * Creates an INSERT INTO ... ON DUPLICATE KEY UPDATE query.
     *
     * @param string $table The name of the table.
     * @param array|object $data The data to insert or update.
     * @param array $exceptFields (optional) The fields to exclude from the update statement. Default is an empty array.
     * @param string $returning (optional) The returning clause for the query. Default is empty string.
     * @return string The generated INSERT INTO ... ON DUPLICATE KEY UPDATE query.
     */
    public function CreateInsertOrUpdate(string $table, array |object $data, array $exceptFields = array(), string $returning = ''): string
    {
        $data = (array) $data;
        $keys = array_keys($data);
        $fields = '(`' . implode("`, `", $keys) . '`)';

        $vals = array_values($data);
        $vs = [];
        foreach ($vals as $val) {
            if (is_null($val)) {
                $val = 'null';
            } elseif (is_bool($val)) {
                $val = '\'' . ($val ? 1 : 0) . '\'';
            } elseif (strstr($val, '[[') === false || strstr($val, ']]') === false) {
                $val = '\'' . addslashes($val) . '\'';
            }
            $vs[] = $val;
        }
        $values = "(" . implode(",", $vs) . ")";

        $updateStatement = '';
        foreach ($data as $k => $v) {
            if (!in_array($k, $exceptFields)) {
                $updateStatement .= ',`' . $k . '`=' . ($v == null ? 'null' : '\'' . addslashes($v) . '\'');
            }
        }

        return "insert into `" . $table . '`' . $fields . ' values ' . $values .
            ' on duplicate key update ' . substr($updateStatement, 1);
    }

    /**
     * Creates a batch INSERT query.
     *
     * @param string $table The name of the table.
     * @param array|object $data The data to insert in batch.
     * @return string The generated batch INSERT query.
     */
    public function CreateBatchInsert(string $table, array |object $data): string
    {
        $keys = array_keys((array) $data[0]);
        $fields = '(`' . implode("`, `", $keys) . '`)';

        $values = '';
        foreach ($data as $row) {
            $row = (array) $row;
            $vals = array_values($row);
            foreach ($vals as $index => $val) {
                if (is_null($val)) {
                    $val = 'null';
                } elseif (is_bool($val)) {
                    $val = '\'' . ($val ? 1 : 0) . '\'';
                } elseif (strstr($val, '[[') === false || strstr($val, ']]') === false) {
                    $val = '\'' . addslashes($val) . '\'';
                }
                $vals[$index] = $val;
            }

            $values .= ",(" . implode(", ", $vals) . ")";
        }
        $values = substr($values, 1);

        return "insert into `" . $table . '`' . $fields . ' values' . $values;
    }

    /**
     * Creates an UPDATE query.
     *
     * @param string $table The name of the table.
     * @param string $condition The condition for updating the records.
     * @param array|object $data The data to update.
     * @return string The generated UPDATE query.
     */
    public function CreateUpdate(string $table, string $condition, array |object $data): string
    {
        $data = (array) $data;
        $q = '';
        foreach ($data as $k => $val) {
            if (is_null($val)) {
                $val = 'null';
            } elseif (is_bool($val)) {
                $val = '\'' . ($val ? 1 : 0) . '\'';
            } elseif (strpos($val, '^') === 0) {
                $val = substr($val, 1);
            } elseif (strstr($val, '[[') === false || strstr($val, ']]') === false) {
                $val = '\'' . addslashes($val) . '\'';
            }
            $q .= ',`' . $k . '`=' . $val;
        }
        return "update `" . $table . '` set ' . substr($q, 1) . ' where ' . $condition;
    }

    /**
     * Creates a DELETE query.
     *
     * @param string $table The name of the table.
     * @param string $condition The condition for deleting the records.
     * @return string The generated DELETE query.
     */
    public function CreateDelete(string $table, string $condition): string
    {
        if (!empty($condition)) {
            $condition = ' where ' . $condition;
        }
        return (empty($condition) ? 'truncate table ' : 'delete from ') . '`' . $table . '`' . $condition;
    }

    /**
     * Creates a SHOW TABLES query.
     *
     * @return string The generated SHOW TABLES query.
     */
    public function CreateShowTables(): string
    {
        return "show tables";
    }

    /**
     * Creates a SHOW COLUMNS FROM query for a specific table.
     *
     * @param string $table The name of the table.
     * @return string The generated SHOW COLUMNS FROM query.
     */
    public function CreateShowField(string $table): string
    {
        return "show columns from `" . $table . '`';
    }

    /**
     * Creates a BEGIN transaction query.
     *
     * @param string|null $type (optional) The type of transaction (e.g., 'readonly', 'readwrite'). Default is null.
     * @return string The generated BEGIN transaction query.
     */
    public function CreateBegin(string $type = null): string
    {
        if($type === 'readonly') {
            return 'start transaction READ ONLY';
        } elseif($type === 'readwrite') {
            return 'start transaction READ WRITE';
        } else {
            return 'start transaction';
        }
    }

    /**
     * Creates a COMMIT transaction query.
     *
     * @return string The generated COMMIT transaction query.
     */
    public function CreateCommit(): string
    {
        return 'commit';
    }

    /**
     * Creates a ROLLBACK transaction query.
     *
     * @return string The generated ROLLBACK transaction query.
     */
    public function CreateRollback(): string
    {
        return 'rollback';
    }

}
