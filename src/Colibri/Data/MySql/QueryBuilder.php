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

namespace Colibri\Data\MySql;

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
     * @param object $data
     * @param string $returning
     * @return string
     * @testFunction testQueryBuilderCreateInsert
     */
    public function CreateInsert($table, $data, $returning = '')
    {
        $data = (array)$data;
        foreach ($data as $key => $value) {
            if (is_null($value)) {
                $value = 'null';
            } else {
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
     * Создает запрос ввода данных или обновления в случае дублирования данных в индексных полях
     *
     * @param string $table
     * @param object $data
     * @param array $exceptFields
     * @param string $returning
     * @return string
     * @testFunction testQueryBuilderCreateInsertOrUpdate
     */
    public function CreateInsertOrUpdate($table, $data, $exceptFields = array(), $returning = '')
    {
        $data = (array)$data;
        $keys = array_keys($data);
        $fields = '(`' . implode("`, `", $keys) . '`)';

        $vals = array_values($data);
        $vs = [];
        foreach ($vals as $val) {
            $vs[] = $val === null ? 'null' : '\'' . addslashes($val) . '\'';
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
     * @param array $data
     * @return string
     * @testFunction testQueryBuilderCreateBatchInsert
     */
    public function CreateBatchInsert($table, $data)
    {
        $keys = array_keys((array)$data[0]);
        $fields = '(`' . implode("`, `", $keys) . '`)';

        $values = '';
        foreach ($data as $row) {
            $row = (array)$row;
            $vals = array_values($row);
            foreach ($vals as $index => $v) {
                if (VariableHelper::IsNull($v)) {
                    $vals[$index] = 'NULL';
                } else {
                    $vals[$index] = '\'' . addslashes($v) . '\'';
                }
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
    public function CreateUpdate($table, $condition, $data)
    {
        $data = (array)$data;
        $q = '';
        foreach ($data as $k => $v) {
            $q .= ',`' . $k . '`=' . (is_null($v) ? 'null' : '\'' . addslashes($v) . '\'');
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
    public function CreateDelete($table, $condition)
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
    public function CreateShowTables()
    {
        return "show tables";
    }

    /**
     * Создает запрос на получение списка полей в таблице
     *
     * @param string $table
     * @return string
     * @testFunction testQueryBuilderCreateShowField
     */
    public function CreateShowField($table)
    {
        return "show columns from " . $table;
    }
}
