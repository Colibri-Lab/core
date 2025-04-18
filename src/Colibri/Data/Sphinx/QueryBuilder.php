<?php


/**
 * Sphinx
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\Sphinx
 */

namespace Colibri\Data\Sphinx;

use Colibri\Common\StringHelper;
use Colibri\Common\VariableHelper;
use Colibri\Data\SqlClient\IQueryBuilder;
use Colibri\Data\Storages\Storage;

/**
 * Class for generating queries for the Sphinx driver.
 *
 * This class implements the IQueryBuilder interface, providing methods to generate various types of SQL queries
 * compatible with the MySql database.
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
    public function CreateInsert(string $table, array|object $data, string $returning = ''): string
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
     * @param array $exceptFields Not used!
     * @param string $returning (optional) The returning clause for the query. Default is empty string.
     * @return string The generated INSERT INTO ... ON DUPLICATE KEY UPDATE query.
     */
    public function CreateInsertOrUpdate(string $table, array |object $data, array $exceptFields = array(), string $returning = ''): string
    {
        return $this->CreateReplace($table, $data, $returning);
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
                $filters[] = '`' . $field . '` ' . $data[0] . ' ' . $data[1];
            }
        } else {
            $filters[] = $filter;
        }

        $orders = [];
        if(is_array($order)) {
            foreach($order as $key => $direction) {
                $orders[] = '`' . $key . '` ' . $direction;
            }
        } else {
            $orders[] = $order;
        }
        return 'select '.(is_array($fields) ? '`' . implode('`,`', $fields) . '`' : $fields).' from `' . $table . '`' .
            (!empty($filters) ? 'where ' . implode(' and ', $filters) : '') . ' '.
            (!empty($orders) ? 'order by ' . implode(',', $orders) : '');
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
        return (empty($condition) ? 'truncate rtindex ' : 'delete from ') . '`' . $table . '`' . $condition;
    }

    public function CreateDrop($table): string
    {
        return 'drop table ' . $table;
    }

    /**
     * Creates a SHOW TABLES query.
     *
     * @return string The generated SHOW TABLES query.
     */
    public function CreateShowTables(?string $table = null, ?string $database = null): string
    {
        return "show tables" . ($table ? " like '" . $table . "'" : "");
    }

    /**
     * Creates a SHOW TABLES query.
     *
     * @return string The generated SHOW TABLES query.
     */
    public function CreateShowIndexes(string $table, ?string $database = null): string
    {
        return 'SHOW INDEX FROM ' . $table;
    }

    /**
     * Creates a SHOW COLUMNS FROM query for a specific table.
     *
     * @param string $table The name of the table.
     * @return string The generated SHOW COLUMNS FROM query.
     */
    public function CreateShowField(string $table, ?string $database = null): string
    {
        return 'DESCRIBE ' . $table;
    }

    /**
     * Creates a BEGIN transaction query.
     *
     * @param string|null $type (optional) The type of transaction (e.g., 'readonly', 'readwrite'). Default is null.
     * @return string The generated BEGIN transaction query.
     */
    public function CreateBegin(?string $type = null): string
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

    public function CreateDefaultStorageTable(string $table, ?string $prefix = null): string|array
    {
        $options = $this->_connection->options;
        $tableOptions = (array)$options['table'];
        $tableOptionsString = VariableHelper::ToString($tableOptions, ', ', '=');
        $ttable = ($prefix ? $prefix . '_' : '') . $table;
        return '
            create table `' . $ttable . '`(
                id BIGINT, 
                content FIELD,
                datecreated bigint, 
                datemodified bigint, 
                datedeleted bigint
            ) OPTION '.($tableOptionsString ? $tableOptionsString : 'rt_mem_limit=256M, min_prefix_len=3').'
        ';
    }
    public function CreateShowStatus(string $table): string
    {
        return 'SHOW INDEX '.$table.' AGENT STATUS';
    }

    public function ProcessFilters(Storage $storage, string $term, ?array $filterFields, ?string $sortField, ?string $sortOrder)
    {

        $filterFields = VariableHelper::ToJsonFilters($filterFields);

        $searchFilters = [];
        foreach($filterFields as $key => $filterData) {
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

        $joinTables = [];
        $fields = [];
        $fieldIndex = 0;
        foreach($searchFilters as $fieldName => $fieldParams) {
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
                        'datecreated' => 'timestamp',
                        'datemodified' => 'timestamp'
                    ][$fieldName],
                    'param' => [
                        'id' => 'integer',
                        'datecreated' => 'integer',
                        'datemodified' => 'integer'
                    ][$fieldName],
                ];
            } else {
                $field = $storage->GetField($fieldName);
            }
            if($field->type === 'json') {
                foreach($fieldParams as $path => $value) {
                    $joinTables[] = '
                        inner join (
                            select
                                {id} as t_'.$fieldIndex.'_id, t_'.$fieldIndex.'.json_field_'.$fieldIndex.'
                            from '.$storage->table.', json_table(
                                {'.$fieldName.'},
                                \''.$path.'\'
                                columns (
                                    json_field_'.$fieldIndex.' varchar(1024) path \'$\'
                                )
                            ) t_'.$fieldIndex.'
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

        $filters = [];
        $params = [];
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

        $types = $storage->accessPoint->allowedTypes;
        foreach($fields as $fieldName => $fieldData) {
            $field = $fieldData[0];
            $value = $fieldData[1];
            $type = $types[$field->type];
            

            if(in_array($field->component, [
                'Colibri.UI.Forms.Date',
                'Colibri.UI.Forms.DateTime',
                'Colibri.UI.Forms.Number'
            ])) {
                if(isset($type['convert'])) {
                    eval('$f = ' . $type['convert'] . ';');
                    $value[0] = $f($value[0]);
                    $value[1] = $f($value[1]);
                }

                if($value[0]) {
                    $filters[] = (strstr($fieldName, 'json_') !== false ? $fieldName : '{' . $fieldName . '}').
                        ' >= [['. $fieldName . '0:' . $field->param . ']]';
                    $params[$fieldName.'0'] = $value[0];
                } 
                if($value[1]) {
                    $filters[] = (strstr($fieldName, 'json_') !== false ? $fieldName : '{' . $fieldName . '}').
                        ' <= [[' . $fieldName . '1:' . $field->param . ']]';
                    $params[$fieldName.'1'] = $value[1];
                }
                
            } else {
                if(!is_array($value)) {
                    $value = [$value];
                }
                $flts = [];
                foreach($value as $index => $v) {
                    if(isset($type['convert'])) {
                        eval('$f = ' . $type['convert'] . ';');
                        $value[0] = $f($v);
                    }
                    $eq = '=';
                    $flts[] = '[['.$fieldName.$index.':'.($field->param ?: 'string').']]';
                    $params[$fieldName.$index] = $v;
                }
                $filters[] = (strstr($fieldName, 'json_') !== false ? $fieldName :
                        '{' . $fieldName . '}') . ' in (' . implode(', ', $flts) . ')';
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



    public function CreateFieldForQuery(string $field, string $table): string
    {
        return $field;
    }

    public function CreateSoftDeleteQuery(string $softDeleteField = 'datedeleted', string $table = ''): string
    {
        return $this->CreateFieldForQuery($softDeleteField, $table) . '=0';
    }

}
