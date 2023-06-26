<?php

/**
 * Драйвер для MySql
 *
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Utils\Config
 * @version 1.0.0
 *
 */

namespace Colibri\Data\PgSql;

use Colibri\Common\VariableHelper;
use Colibri\Data\SqlClient\IQueryBuilder;
use Colibri\Utils\Debug;

/**
 * Класс генератор запросов для драйвераMySql
 * @testFunction testQueryBuilder
 */
class QueryBuilder implements IQueryBuilder
{
    /**
     * Создает запрос ввода данных
     *
     * @param string $table
     * @param array|object $data
     * @param string $returning
     * @return string
     * @testFunction testQueryBuilderCreateInsert
     */
    public function CreateInsert(string $table, array |object $data, string $returning = ''): string
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

        return "insert into " . $table . $fields . ' values' . $values;
    }

    /**
     * Создает запрос ввода данных
     *
     * @param string $table
     * @param array|object $data
     * @param string $returning
     * @return string
     * @testFunction testQueryBuilderCreateInsert
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

        return "replace into " . $table . $fields . ' values' . $values;
    }

    /**
     * Создает запрос ввода данных или обновления в случае дублирования данных в индексных полях
     *
     * @param string $table
     * @param array|object $data
     * @param array $exceptFields
     * @param string $returning
     * @return string
     * @testFunction testQueryBuilderCreateInsertOrUpdate
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

        return "insert into " . $table . $fields . ' values ' . $values . ' on duplicate key update ' . substr($updateStatement, 1);
    }

    /**
     * Создает запрос ввода данных пачкой
     *
     * @param string $table
     * @param array|object $data
     * @return string
     * @testFunction testQueryBuilderCreateBatchInsert
     */
    public function CreateBatchInsert(string $table, array |object $data)
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

        return "insert into " . $table . $fields . ' values' . $values;
    }

    /**
     * Создает запрос на обновление данных
     *
     * @param string $table
     * @param string $condition
     * @param object $data
     * @return string
     * @testFunction testQueryBuilderCreateUpdate
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
            } elseif (strstr($val, '[[') === false || strstr($val, ']]') === false) {
                $val = '\'' . addslashes($val) . '\'';
            }
            $q .= ',`' . $k . '`=' . $val;
        }
        return "update " . $table . ' set ' . substr($q, 1) . ' where ' . $condition;
    }

    /**
     * Создает запрос на удаление данных
     *
     * @param string $table
     * @param string $condition
     * @return string
     * @testFunction testQueryBuilderCreateDelete
     */
    public function CreateDelete(string $table, string $condition): string
    {
        if (!empty($condition)) {
            $condition = ' where ' . $condition;
        }
        return (empty($condition) ? 'truncate table ' : 'delete from ') . $table . $condition;
    }

    /**
     * Создает запрос на получение списка таблиц
     *
     * @return string
     * @testFunction testQueryBuilderCreateShowTables
     */
    public function CreateShowTables(): string
    {
        return 'SELECT * FROM pg_catalog.pg_tables';
    }

    /**
     * Создает запрос на получение списка полей в таблице
     *
     * @param string $table
     * @return string
     * @testFunction testQueryBuilderCreateShowField
     */
    public function CreateShowField(string $table, ?string $schema = null): string
    {
        return 'SELECT * FROM information_schema.columns WHERE '.($schema ? 'table_schema = \''.$schema.'\' AND ' : '').'table_name = \''.$table.'\'';
    }
}