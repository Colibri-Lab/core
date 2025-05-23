<?php

/**
 * PgSql
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\PgSql
 */

namespace Colibri\Data\PgSql;

use Colibri\Common\StringHelper;
use Colibri\Common\VariableHelper;
use Colibri\Data\SqlClient\IQueryBuilder;
use Colibri\Data\Storages\Storage;
use Colibri\Utils\Debug;

/**
 * Class for generating queries for the PostgreSql driver.
 *
 * This class implements the IQueryBuilder interface, providing methods to generate various types of SQL queries
 * compatible with the PostgreSql database.
 *
 */
class QueryBuilder implements IQueryBuilder
{
    private Connection $_connection;
    public function __construct(Connection $connection)
    {
        $this->_connection = $connection;
    }
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
            } elseif (strstr($value, '[[') === false || strstr($value, ']]') === false) {
                $value = '\'' . addslashes($value) . '\'';
            }
            $data[$key] = $value;
        }

        $keys = array_keys($data);
        $fields = '("' . join('", "', $keys) . '")';

        $vals = array_values($data);
        $values = "(" . join(", ", $vals) . ")";

        $table = '"'. implode('"."', explode('.', $table)) . '"';

        return 'insert into ' . $table . $fields . ' values' . $values;
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
        $fields = '("' . join('", "', $keys) . '")';

        $vals = array_values($data);
        $values = "(" . join(", ", $vals) . ")";
        $table = '"'. implode('"."', explode('.', $table)) . '"';

        return 'replace into ' . $table . $fields . ' values' . $values;
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
        $fields = '("' . implode('", "', $keys) . '")';

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
                $updateStatement .= ',"' . $k . '"=' . ($v == null ? 'null' : '\'' . addslashes($v) . '\'');
            }
        }
        $table = '"'. implode('"."', explode('.', $table)) . '"';

        return 'insert into ' . $table . $fields . ' values ' . $values . ' on duplicate key update ' . substr($updateStatement, 1);
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
        $fields = '("' . implode('", "', $keys) . '")';

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
        $table = '"'. implode('"."', explode('.', $table)) . '"';

        return 'insert into ' . $table . $fields . ' values' . $values;
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
            } elseif (strstr($val, '[[') === false || strstr($val, ']]') === false) {
                $val = '\'' . addslashes($val) . '\'';
            }
            $q .= ',"' . $k . '"=' . $val;
        }

        $table = '"'. implode('"."', explode('.', $table)) . '"';
        return 'update ' . $table . ' set ' . substr($q, 1) . ' where ' . $condition;
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
        $table = '"'. implode('"."', explode('.', $table)) . '"';
        return (empty($condition) ? 'truncate table ' : 'delete from ') . $table . $condition;
    }

    /**
     * Creates a SHOW TABLES query.
     *
     * @return string The generated SHOW TABLES query.
     */
    public function CreateShowTables(?string $tableFilter = null, ?string $database = null): string
    {
        return 'SELECT * FROM pg_catalog.pg_tables WHERE true' . ($tableFilter ? ' and tablename=\''.$tableFilter.'\'' : '') . ' and schemaname=\'public\'';
    }

    /**
     * Creates a SHOW TABLES query.
     *
     * @return string The generated SHOW TABLES query.
     */
    public function CreateShowIndexes(string $table, ?string $database = null): string
    {
        return 'SELECT * FROM pg_catalog.pg_indexes WHERE true' . ($table ? ' and tablename=\''.$table.'\'' : '') . ($table ? ' and schemaname=\'public\'' : '');
    }

    /**
     * Creates a SELECT query.
     * @param string $table The name of the table.
     * @param array|string $fields The fields to select.
     * @param array|string $filter The filter for selecting the records.
     * @param array|string $order The order for selecting the records.
     * @return string
     */
    public function CreateSelect(string $table, array|string $fields, array|string $filter, array|string $order): string
    {

        $filters = [];
        if(is_array($filter)) {
            foreach($filter as $field => $data) {
                $filters[] = '"' . $field . '" ' . $data[0] . ' ' . $data[1];
            }
        } elseif ($filter) {
            $filters[] = $filter;
        }

        $orders = [];
        if(is_array($order)) {
            foreach($order as $key => $direction) {
                $orders[] = '"' . $key . '" ' . $direction;
            }
        } elseif ($order) {
            $orders[] = $order;
        }

        return 'select '.(is_array($fields) ? '"' . implode('","', $fields) . '"' : $fields).' from "' . $table . '"' .
            (!empty($filters) ? ' where ' . implode(' and ', $filters) : '') .
            (!empty($orders) ? ' order by ' . implode(',', $orders) : '');
    }

    /**
     * Creates a SHOW COLUMNS FROM query for a specific table.
     *
     * @param string $table The name of the table.
     * @return string The generated SHOW COLUMNS FROM query.
     */
    public function CreateShowField(string $table, ?string $database = null): string
    {
        return 'SELECT * FROM information_schema.columns WHERE '.($database ? 'table_catalog = \''.$database.'\' AND ' : '').'table_name = \''.$table.'\'';
    }

    public function CreateShowStatus(string $table): string
    {
        // return 'SHOW TABLE STATUS LIKE \''.$table.'\'';
        return '';
    }

    public function CreateDrop($table): string
    {
        return 'drop table ' . $table;
    }

    /**
     * Creates a BEGIN transaction query.
     *
     * @param string|null $type (optional) The type of transaction (e.g., 'readonly', 'readwrite'). Default is null.
     * @return string The generated BEGIN transaction query.
     */
    public function CreateBegin(?string $type = null): string
    {
        return 'begin transaction';
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

    public function CreateFieldForQuery(string $field, string $table): string
    {
        return '"' . $table . '"."' . $field . '"';
    }


    public function CreateSoftDeleteQuery(string $softDeleteField = 'datedeleted', string $table = ''): string
    {
        return $this->CreateFieldForQuery($softDeleteField, $table) . ' is null';
    }

    public function CreateDefaultStorageTable(string $table, ?string $prefix = null): string|array
    {
        return ['
            create table "' . ($prefix ? $prefix . '_' : '') . $table . '"(
                "' . $table . '_id" int8 NOT NULL GENERATED ALWAYS AS IDENTITY, 
                "' . $table . '_datecreated" timestamp not null default CURRENT_TIMESTAMP, 
                "' . $table . '_datemodified" timestamp not null default CURRENT_TIMESTAMP, 
                "' . $table . '_datedeleted" timestamp null,
                PRIMARY KEY ("' . $table . '_id")
            )
        ',
        'create index if not exists "' . $table . '_datecreated_idx" on "'.$table.'"("' . $table . '_datecreated")',
        'create index if not exists "' . $table . '_datemodified_idx" on "'.$table.'"("' . $table . '_datemodified")',
        'create index if not exists "' . $table . '_datedeleted_idx" on "'.$table.'"("' . $table . '_datedeleted")',
        ];
    }


    public function ProcessFilters(Storage $storage, string $term, ?array $filterFields, ?string $sortField, ?string $sortOrder)
    {

        $filterFields = VariableHelper::ToJsonFilters($filterFields);

        $searchFilters = [];
        foreach($filterFields as $key => $filterData) {
            if($key === '_') {
                $searchFilters[$key] = $filterData;
                continue;
            }
            $parts = StringHelper::Explode($key, ['[', '.']);
            $fieldName = $parts[0];
            $filterPath = substr($key, strlen($fieldName));
            $filterPath = '$'.str_replace('[0]', '[*]', $filterPath);

            if($filterPath === '$') {
                $searchFilters[$fieldName] = $filterData;
            } else {
                if(!isset($searchFilters[$fieldName])) {
                    $searchFilters[$fieldName] = [];
                }
                $searchFilters[$fieldName][$filterPath] = $filterData;
            }
        }

        $filters = [];
        $params = [];
        
        $joinTables = [];
        $fields = [];
        $fieldIndex = 0;
        foreach($searchFilters as $fieldName => $fieldParams) {
            if($fieldName === '_') {
                $filters[] = $fieldParams;
                continue;
            }
            if(in_array($fieldName, ['id', 'datecreated', 'datemodified'])) {
                $field = (object)[
                    'component' => $fieldName === 'id' ? 'Colibri.UI.Forms.Number' : 'Colibri.UI.Forms.DateTime',
                    'desc' => [
                        'id' => 'ID',
                        'datecreated' => 'Дата создания',
                        'datemodified' => 'Дата изменения'
                    ][$fieldName],
                    'type' => [
                        'id' => 'int',
                        'datecreated' => 'datetime',
                        'datemodified' => 'datetime'
                    ][$fieldName],
                    'param' => [
                        'id' => 'integer',
                        'datecreated' => 'string',
                        'datemodified' => 'string'
                    ][$fieldName],
                ];
            } else {
                $field = $storage->GetField($fieldName);
            }
            if($field->type === 'json' || $field->type === 'jsonb') {
                foreach($fieldParams as $path => $value) {
                    $joinTables[] = '
                        inner join (
                            select
                                {id} as t_'.$fieldIndex.'_id, t_'.$fieldIndex.' #>> \'{}\' as json_field_'.$fieldIndex.'
                            from '.$storage->table.', jsonb_path_query({'.$fieldName.'}, \''.$path.'\') t_'.$fieldIndex.'
                        ) json_table_'.$fieldIndex.' on '.
                        'json_table_'.$fieldIndex.'.t_'.$fieldIndex.'_id='.$storage->table.'.{id}';

                    $fieldPath = str_replace('[*]', '', $path);
                    $fieldPath = str_replace('$', '', $fieldPath);
                    $fieldPath = str_replace('.', '/', $fieldPath);
                    $fieldPath = str_replace('//', '/', $fieldName . '/' . $fieldPath);
                    $fields['json_field_'.$fieldIndex] = [$storage->GetField($fieldPath), $value];
                    $fieldIndex++;
                }
            } else {
                $fields[$fieldName] = [$field, $fieldParams];
            }
        }

        if($term) {
            $termFilters = [];
            foreach ($storage->fields as $field) {
                if ($field->class === 'string') {
                    $termFilters[] = '{' . $field->name . '} like [[term:string]]';
                }
            }
            $filters[] = '(' . implode(' or ', $termFilters) . ')';
            $params['term'] = '%' . $term . '%';
        }

        foreach($fields as $fieldName => $fieldData) {
            $field = $fieldData[0];
            $value = $fieldData[1];

            if(in_array($field->component, [
                'Colibri.UI.Forms.Date',
                'Colibri.UI.Forms.DateTime',
                'Colibri.UI.Forms.Number'
            ])) {
                $fname = (strstr($fieldName, 'json_') !== false ? $fieldName : '{' . $fieldName . '}');

                $f[] = $fname . ' >= [['. $fieldName . '0:' . $field->param . ']]';
                $params[$fieldName.'0'] = $value[0];
                
                if(isset($value[1])) {
                    $f[] = $fname . ' <= [[' . $fieldName . '1:' . $field->param . ']]';
                    $params[$fieldName.'1'] = $value[1];
                }

                $filters[] = implode(' and ', $f);
            } else {
                if(!is_array($value)) {
                    $value = [$value];
                }
                $flts = [];
                foreach($value as $index => $v) {
                    $eq = '=';
                    if($field->param === 'string') {
                        $eq = 'like';
                    }
                    $flts[] = (strstr($fieldName, 'json_') !== false ? $fieldName :
                        '{' . $fieldName . '}').' '.$eq.' [['.$fieldName.$index.':'.($field->param ?: 'string').']]';
                    $params[$fieldName.$index] = $eq === 'like' ? '%' . $v . '%' : $v;
                }
                $filters[] = implode(' or ', $flts);
            }

        }


        if(!empty($joinTables)) {
            $params['__joinTables'] = $joinTables;
        }

        if (!$sortField) {
            $sortField = '{id}';
        } else {
            $sortField = '{' . $sortField . '}';
        }
        if (!$sortOrder) {
            $sortOrder = 'asc';
        }

        return [implode(' and ', $filters), $sortField . ' ' . $sortOrder, $params];

    }

}
