<?php

/**
 * MsSql
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\MsSql
 */

namespace Colibri\Data\MsSql;

use Colibri\Data\SqlClient\Command as SqlCommand;
use Colibri\Data\MsSql\Exception as MsSqlException;
use Colibri\Data\SqlClient\IDataReader;
use Colibri\Utils\Logs\Logger;
use MsSqli_stmt;

/**
 * Represents a final database command, extending SqlCommand.
 *
 * This class provides functionality for executing SQL commands and preparing statements with parameters.
 * It inherits properties and methods from the SqlCommand class.
 */
final class Command extends SqlCommand
{
    /**
     * Prepares a statement with parameters.
     *
     * @param string $query The query with placeholders for parameters.
     * @return MsSqli_stmt The prepared statement.
     * @throws MsSqlException If no parameters are provided or if there's an issue with the query.
     */
    private function _prepareStatement(string $query): mixed
    {

        if (!$this->_params) {
            throw new MsSqlException('no params', 0);
        }

        $res = preg_match_all('/\[\[([^\]]+)\]\]/', $query, $matches);
        if ($res == 0) {
            throw new MsSqlException('no params', 0);
        }

        $typesAliases = ['integer' => 'i', 'double' => 'd', 'string' => 's', 'blob' => 'b'];

        $types = [];
        $values = [];
        foreach ($matches[1] as $match) {

            // если тип не указан то берем string
            if (strstr($match, ':') === false) {
                $match = $match . ':string';
            }

            $matching = explode(':', $match);
            if (!is_array($this->_params[$matching[0]])) {
                $types[] = $typesAliases[$matching[1]];
                $values[] = $this->_params[$matching[0]];
                $query = str_replace('[[' . $match . ']]', '?', $query);
            } else {
                $types = array_merge(
                    $types,
                    array_fill(
                        0,
                        count($this->_params[$matching[0]]),
                        $typesAliases[$matching[1]]
                    )
                );
                $values = array_merge(
                    $values,
                    $this->_params[$matching[0]]
                );
                $query = str_replace(
                    '[[' . $match . ']]',
                    implode(',', array_fill(0, count($this->_params[$matching[0]]), '?')),
                    $query
                );
            }
        }

        $stmt = MsSqli_prepare($this->_connection->resource, $query);
        if (!$stmt) {
            throw new MsSqlException(
                MsSqli_error($this->_connection->resource),
                MsSqli_errno($this->_connection->resource)
            );
        }

        // чертов бред!
        // ! поменять когда будет php7
        $params = [&$stmt, implode('', $types)];
        for ($i = 0; $i < count($values); $i++) {
            $params[] = & $values[$i];
        }

        call_user_func_array('MsSqli_stmt_bind_param', $params);

        return $stmt;
    }

    /**
     * Executes the command and returns a data reader.
     *
     * @param bool $info Whether to execute a query to obtain the affected variable. Default is true.
     * @return IDataReader The data reader.
     * @throws MsSqlException If there's an issue executing the query.
     */
    public function ExecuteReader(bool $info = true): IDataReader
    {

        // выбираем базу данныx, с которой работает данный линк
        MsSqli_select_db($this->_connection->resource, $this->_connection->database);

        // если нужно посчитать количество результатов
        $affected = null;
        if ($this->page > 0 && $info) {

            // выполняем запрос для получения количества результатов без limit-ов
            $limitQuery = 'select count(*) as affected from (' . $this->query . ') tbl';
            if ($this->_params) {
                $stmt = $this->_prepareStatement($limitQuery);
                MsSqli_stmt_execute($stmt);
                $ares = MsSqli_stmt_get_result($stmt);
            } else {
                $ares = MsSqli_query($this->_connection->resource, $limitQuery);
            }
            if (!($ares instanceof \MsSqli_result)) {
                throw new MsSqlException(
                    MsSqli_error($this->_connection->resource) . ' query: ' . $limitQuery,
                    MsSqli_errno($this->_connection->resource)
                );
            }
            if (MsSqli_num_rows($ares) > 0) {
                $affected = MsSqli_fetch_object($ares)->affected;
            }

        }

        // добавляем к тексту запроса limit-ы
        $preparedQuery = $this->PrepareQueryString();

        // выполняем запрос
        if ($this->_params) {
            $stmt = $this->_prepareStatement($preparedQuery);
            MsSqli_stmt_execute($stmt);
            $res = MsSqli_stmt_get_result($stmt);
        } else {
            $res = MsSqli_query($this->_connection->resource, $preparedQuery);
        }
        

        if (!($res instanceof \MsSqli_result)) {
            throw new MsSqlException(
                MsSqli_error($this->_connection->resource) . ' query: ' . $preparedQuery,
                MsSqli_errno($this->_connection->resource)
            );
        }

        return new DataReader($res, $affected, $preparedQuery);

    }

    /**
     * Executes the command and returns query information.
     *
     * @param string|null $dummy (Unused parameter) Dummy parameter for compatibility. Default is null.
     * @return QueryInfo The query information.
     * @throws MsSqlException If there's an issue executing the query.
     */
    public function ExecuteNonQuery(?string $dummy = null): QueryInfo
    {
        MsSqli_select_db($this->_connection->resource, $this->_connection->database);

        if ($this->_params) {
            $stmt = $this->_prepareStatement($this->query);
            MsSqli_stmt_execute($stmt);
            return new QueryInfo(
                $this->type,
                MsSqli_stmt_insert_id($stmt),
                MsSqli_stmt_affected_rows($stmt),
                MsSqli_stmt_error($stmt),
                $this->query
            );
        } else {
            MsSqli_query($this->_connection->resource, $this->query);
            return new QueryInfo(
                $this->type,
                MsSqli_insert_id($this->connection->resource),
                MsSqli_affected_rows($this->connection->resource),
                MsSqli_error($this->connection->resource),
                $this->query
            );
        }
    }

    /**
     * Prepares the query string with pagination and other necessary adjustments.
     *
     * @return string The prepared query string.
     */
    public function PrepareQueryString(): string
    {
        $query = $this->query;
        if ($this->_page > 0) { //  && strstr($query, "limit") === false
            $query .= ' limit ' . (($this->_page - 1) * $this->_pagesize) . ', ' . $this->_pagesize;
        }
        return $query;
    }

    public function Migrate(Logger $logger, string $storage, array $xstorage): void
    {

        $prefix = isset($xstorage['prefix']) ? $xstorage['prefix'] : '';
        $table = $prefix ? $prefix . '_' . $storage : $storage;

        $queryBuilder = new QueryBuilder();

        $CreateReader = function(string $query, $connection) {
            $tableCommand = new Command($query, $connection);
            return $tableCommand->ExecuteReader();
        };

        $Exec = function(string $query, $connection) {
            $tableCommand = new Command($query, $connection);
            return $tableCommand->ExecuteNonQuery();
        };

        $UpdateDefaultAndLength = function(
            string $field,
            string $type,
            bool $required,
            ?int $length,
            mixed $default
        ): array {
    
            if (\is_bool($default)) {
                $default = $default ? 'TRUE' : 'FALSE';
            }
    
            if ($type == 'json') {
                $default = $default ? '(' . $default . ')' : null;
                $required = false;
            } elseif (strstr($type, 'enum') !== false) {
                $default = $default ? "'" . $default . "'" : null;
            } elseif (strstr($type, 'char') !== false) {
                $default = $default ? "'" . $default . "'" : null;
            }
    
            if ($type == 'varchar' && !$length) {
                $length = 255;
            }
    
            return [$required, $length, $default];
    
        };

        $reader = $CreateReader($queryBuilder->CreateShowTables($table), $this->_connection);
        if ($reader->Count() == 0) {
            $logger->error($table . ': Storage destination not found: creating');

            // create the table
            $res = $Exec($queryBuilder->CreateDefaultStorageTable($table, $prefix), $this->_connection);
            if ($res->error) {
                $logger->error($table . ': Can not create destination: ' . $res->query);
                throw new Exception('Can not create destination: ' . $res->query);
            }
        }

        try {

            $fieldsReader = $CreateReader($queryBuilder->CreateShowField($table, $this->_connection->database), $this->_connection);
            $ofields = [];
            while($field = $fieldsReader->Read()) {
                $f = $this->_connection->ExtractFieldInformation($field);
                $ofields[$f->Field] = $f;
            }

            $indexesReader = $CreateReader($queryBuilder->CreateShowIndexes($table), $this->_connection);
            $indices = [];
            while ($index = $indexesReader->Read()) {
                $i = $this->_connection->ExtractIndexInformation($index);
                
                if (!isset($indices[$i->Name])) {
                    $i->Columns = [($i->ColumnPosition - 1) => $i->Columns[0]];
                    $indices[$i->Name] = $i;
                } else {
                    $indices[$i->Name]->Columns[$i->ColumnPosition - 1] = $i->Columns[0];
                }
                
            }

        } catch(\Throwable $e) {
            $logger->error($table . ' does not exists');
        }

        $virutalFields = [];

        $xfields = $xstorage['fields'] ?? [];
        $logger->error($table . ': Checking fields');
        foreach ($xfields as $fieldName => $xfield) {
            $fname = $storage . '_' . $fieldName;
            $fparams = $xfield['params'] ?? [];

            if (($xfield['virtual'] ?? false) === true) {
                $virutalFields[$fieldName] = $xfield;
                continue;
            }

            if ($xfield['type'] == 'enum') {
                $xfield['type'] .= isset($xfield['values']) && $xfield['values'] ? '(' . implode(',', array_map(function ($v) {
                    return '\'' . $v['value'] . '\'';
                }, $xfield['values'])) . ')' : '';
            } elseif ($xfield['type'] === 'bool' || $xfield['type'] === 'boolean') {
                $xfield['type'] = 'tinyint';
                $xfield['length'] = 1;
                if(isset($xfield['default'])) {
                    $xfield['default'] = $xfield['default'] === 'true' ? 1 : 0;
                }
            } elseif ($xfield['type'] === 'json') {
                $fparams['required'] = false;
            }

            $xdesc = isset($xfield['desc']) ? json_encode($xfield['desc'], JSON_UNESCAPED_UNICODE) : '';
            if (!isset($ofields[$fname])) {
                $logger->error($storage . ': ' . $fieldName . ': Field destination not found: creating');

                $length = isset($xfield['length']) ? $xfield['length'] : null;
                $default = isset($xfield['default']) ? $xfield['default'] : null;
                $required = isset($fparams['required']) ? $fparams['required'] : false;
                $type = $xfield['type'];

                [$required, $length, $default] = $UpdateDefaultAndLength($field, $type, $required, $length, $default);

                // ! специфика UUID нужно выключить параметр sql_log_bin
                $sqlLogBinVal = 0;
                if (strstr($default, 'UUID') !== false) {

                    $reader = $CreateReader('SELECT @@sql_log_bin as val', $this->_connection);
                    $sqlLogBinVal = $reader->Read()->val;
                    if ($sqlLogBinVal == 1) {
                        $Exec('set sql_log_bin=0', $this->_connection);
                    }
                }

                $res = $Exec('
                    ALTER TABLE `' . ($prefix ? $prefix . '_' : '') . $table . '` 
                    ADD COLUMN `' . $table . '_' . $field . '` ' . $type . ($length ? '(' . $length . ')' : '') . ($required ? ' NOT NULL' : ' NULL') . ' 
                    ' . ($default ? 'DEFAULT ' . $default . ' ' : '') . ($xdesc ? ' COMMENT \'' . $xdesc . '\'' : ''), 
                    $this->_connection
                );

                if ($sqlLogBinVal == 1) {
                    $Exec('set sql_log_bin=1', $this->_connection);
                }

                if ($res->error) {
                    $logger->error($table . ': Can not save field: ' . $res->query);
                    throw new Exception('Can not save field: ' . $res->query);
                }

            } else {
                // проверить на соответствие
                $ofield = $ofields[$fname];

                $required = isset($fparams['required']) ? $fparams['required'] : false;
                $default = isset($xfield['default']) ? $xfield['default'] : null;
                [, $length,] = $UpdateDefaultAndLength($fieldName, $xfield['type'], $required, $xfield['length'] ?? null, $default);

                $orType = $ofield->Type != $xfield['type'] . ($length ? '(' . $length . ')' : '');
                $orDefault = $ofield->Default != $default;
                $orRequired = $required != ($ofield->Null == 'NO');

                $length = isset($xfield['length']) ? $xfield['length'] : null;
                $default = isset($xfield['default']) ? $xfield['default'] : null;
                $required = isset($fparams['required']) ? $fparams['required'] : false;
                $type = $xfield['type'];

                if ($orType || $orDefault || $orRequired) {
                    $logger->error($storage . ': ' . $fieldName . ': Field destination changed: updating');

                    [$required, $length, $default] = $UpdateDefaultAndLength($field, $type, $required, $length, $default);

                    $res = $Exec(
                        'ALTER TABLE `' . ($prefix ? $prefix . '_' : '') . $table . '` 
                        MODIFY COLUMN `' . $table . '_' . $field . '` ' . $type . ($length ? '(' . $length . ')' : '') . ($required ? ' NOT NULL' : ' NULL') . ' ' . 
                        (!is_null($default) ? 'DEFAULT ' . $default . ' ' : '') . ($xdesc ? 'COMMENT \'' . $xdesc . '\'' : ''),
                        $this->_connection
                    );
                    if ($res->error) {
                        $logger->error($table . ': Can not save field: ' . $res->query);
                        throw new Exception('Can not save field: ' . $res->query);
                    }

                }
            }
        }

        foreach ($virutalFields as $fieldName => $xVirtualField) {
            $fname = $storage . '_' . $fieldName;
            $fparams = $xVirtualField['params'] ?? [];
            $xdesc = isset($xVirtualField['desc']) ? json_encode($xVirtualField['desc'], JSON_UNESCAPED_UNICODE) : '';
            if (!isset($ofields[$fname])) {
                $length = isset($xVirtualField['length']) ? $xVirtualField['length'] : null;
                $res = $Exec('
                    ALTER TABLE `' . ($prefix ? $prefix . '_' : '') . $table . '` 
                    ADD COLUMN `' . $table . '_' . $field . '` ' . $xVirtualField['type'] . ($length ? '(' . $length . ')' : '') . ' 
                    GENERATED ALWAYS AS (' . $xVirtualField['expression'] . ') STORED ' .
                    ($xdesc ? ' COMMENT \'' . $xdesc . '\'' : ''), $this->_connection);

                if ($res->error) {
                    $logger->error($table . ': Can not save field: ' . $res->query);
                    throw new Exception('Can not save field: ' . $res->query);
                }
                
            } else {
                $ofield = $ofields[$fname];

                $required = isset($fparams['required']) ? $fparams['required'] : false;
                $expression = isset($xVirtualField['expression']) ? $xVirtualField['expression'] : null;

                $orType = $ofield->Type != $xVirtualField['type'] . ($length ? '(' . $length . ')' : '');
                $orExpression = $ofield->Expression != $expression;
                $orRequired = $required != ($ofield->Null == 'NO');

                if ($orType || $orExpression || $orRequired) {
                    $logger->error($storage . ': ' . $fieldName . ': Field destination changed: updating');

                    $length = isset($xVirtualField['length']) ? $xVirtualField['length'] : null;
                    
                    $res = $Exec(
                        'ALTER TABLE `' . ($prefix ? $prefix . '_' : '') . $table . '` 
                        MODIFY COLUMN `' . $table . '_' . $field . '` ' . $xVirtualField['type'] . ($length ? '(' . $length . ')' : '') .
                        ' GENERATED ALWAYS AS (' . $expression . ') STORED ' .
                        ($xdesc ? ' COMMENT \'' . $xdesc . '\'' : ''),
                        $this->_connection
                    );
                    if ($res->error) {
                        $logger->error($table . ': Can not save field: ' . $res->query);
                        throw new Exception('Can not save field: ' . $res->query);
                    }

                }

            }
        }

        $xindexes = isset($xstorage['indices']) ? $xstorage['indices'] : [];
        $logger->error($storage . ': Checking indices');
        foreach ($xindexes as $indexName => $xindex) {
            if (!isset($indices[$indexName])) {
                $logger->error($storage . ': ' . $indexName . ': Index not found: creating');
                
                $method = isset($xindex['method']) ? $xindex['method'] : 'BTREE';
                if ($type === 'FULLTEXT') {
                    $method = '';
                }
        
                $res = $Exec('
                    ALTER TABLE `' . ($prefix ? $prefix . '_' : '') . $table . '` 
                    ADD' . ($xindex['type'] !== 'NORMAL' ? ' ' . $xindex['type'] : '') . ' INDEX `' . $indexName . '` (`' . 
                        $table . '_' . implode('`,`' . $table . '_', $xindex['fields']) . '`) ' . 
                    ($method ? ' USING ' . $method : '') . '
                ', $this->_connection);
                if ($res->error) {
                    $logger->error($table . ': Can not create index: ' . $res->query);
                    throw new Exception('Can not create index: ' . $res->query);
                }
            } else {
                $oindex = $indices[$indexName];
                $fields1 = $storage . '_' . implode(',' . $storage . '_', $xindex['fields']);
                $fields2 = implode(',', $oindex->Columns);

                $xtype = isset($xindex['type']) ? $xindex['type'] : 'NORMAL';
                $xmethod = isset($xindex['method']) ? $xindex['method'] : 'BTREE';
                if ($xtype === 'FULLTEXT') {
                    $xmethod = '';
                }

                $otype = 'NORMAL';
                $omethod = 'BTREE';
                if ($oindex->Type == 'FULLTEXT') {
                    $otype = 'FULLTEXT';
                    $omethod = '';
                }
                if ($oindex->NonUnique == 0) {
                    $otype = 'UNIQUE';
                    $omethod = $oindex->Type;
                }

                if ($fields1 != $fields2 || $xtype != $otype || $xmethod != $omethod) {
                    $logger->error($storage . ': ' . $indexName . ': Index changed: updating');

                    $res = $Exec('
                        ALTER TABLE `' . ($prefix ? $prefix . '_' : '') . $table . '` 
                        DROP INDEX `' . $indexName . '`
                    ', $this->_connection);
                    if ($res->error) {
                        $logger->error($table . ': Can not delete index: ' . $res->query);
                        throw new Exception('Can not delete index: ' . $res->query);
                    }

                    $res = $Exec('
                        ALTER TABLE `' . $table . '` 
                        ADD' . ($xtype !== 'NORMAL' ? ' ' . $xtype : '') . ' INDEX `' . $indexName . 
                        '` (`' . $table . '_' . implode('`,`' . $table . '_', $xindex['fields']) . '`) ' . 
                            ($xmethod ? ' USING ' . $xmethod : '') . '
                    ', $this->_connection);
                    if ($res->error) {
                        $logger->error($table . ': Can not create index: ' . $res->query);
                        throw new Exception('Can not create index: ' . $res->query);
                    }

                }
            }
        }
    }

}
