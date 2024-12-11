<?php

/**
 * Sphinx
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\Sphinx
 */

namespace Colibri\Data\Sphinx;

use Colibri\Data\SqlClient\Command as SqlCommand;
use Colibri\Data\Sphinx\Exception as SphinxException;
use Colibri\Data\SqlClient\IDataReader;
use Colibri\Utils\Logs\Logger;
use mysqli_stmt;

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
     * @return mysqli_stmt The prepared statement.
     * @throws SphinxException If no parameters are provided or if there's an issue with the query.
     */
    private function _prepareStatement(string $query): string
    {

        if (!$this->_params) {
            throw new SphinxException('no params', 0);
        }

        $params = $this->_params;
        return preg_replace_callback('/\[\[([^\]]+)\]\]/', function($match) use($params) {
            $match = $match[1];
            if (strstr($match, ':') === false) {
                $match = $match . ':string';
            }

            $matching = explode(':', $match);
            switch($matching[1]) {
                case 'integer': 
                case 'double': 
                    return $params[$matching[0]];
                case 'string':
                case 'blob':
                default:
                    return '\'' . $params[$matching[0]] . '\'';
            }
        }, $query);
        
    }

    /**
     * Executes the command and returns a data reader.
     *
     * @param bool $info Whether to execute a query to obtain the affected variable. Default is true.
     * @return IDataReader The data reader.
     * @throws SphinxException If there's an issue executing the query.
     */
    public function ExecuteReader(bool $info = true): IDataReader
    {

        // добавляем к тексту запроса limit-ы
        $preparedQuery = $this->PrepareQueryString();

        // выполняем запрос
        if ($this->_params) {
            $preparedQuery = $this->_prepareStatement($preparedQuery);
        }
        $res = mysqli_query($this->_connection->resource, $preparedQuery);
        
        $affected = null;
        if ($this->page > 0 && $info) {

            // выполняем запрос для получения количества результатов без limit-ов
            $limitQuery = 'SHOW META like \'total\'';
            $ares = mysqli_query($this->_connection->resource, $limitQuery);
            if (!($ares instanceof \mysqli_result)) {
                throw new SphinxException(
                    mysqli_error($this->_connection->resource) . ' query: ' . $limitQuery,
                    mysqli_errno($this->_connection->resource)
                );
            }
            if (mysqli_num_rows($ares) > 0) {
                $affected = mysqli_fetch_object($ares)->Value;
            }

        }
        if (!($res instanceof \mysqli_result)) {
            throw new SphinxException(
                mysqli_error($this->_connection->resource) . ' query: ' . $preparedQuery,
                mysqli_errno($this->_connection->resource)
            );
        }

        return new DataReader($res, $affected, $preparedQuery);

    }

    /**
     * Executes the command and returns query information.
     *
     * @param string|null $dummy (Unused parameter) Dummy parameter for compatibility. Default is null.
     * @return QueryInfo The query information.
     * @throws SphinxException If there's an issue executing the query.
     */
    public function ExecuteNonQuery(?string $dummy = null): QueryInfo
    {
        $query = $this->query;
        if ($this->_params) {
            $query = $this->_prepareStatement($query);
        }
        
        mysqli_query($this->_connection->resource, $query);
        return new QueryInfo(
            $this->type,
            mysqli_insert_id($this->connection->resource),
            mysqli_affected_rows($this->connection->resource),
            mysqli_error($this->connection->resource),
            $this->query
        );
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

        $queryBuilder = new QueryBuilder($this->_connection);

        $CreateReader = function(string $query, $connection) {
            $tableCommand = new Command($query, $connection);
            return $tableCommand->ExecuteReader();
        };

        $Exec = function(string $query, $connection) {
            $tableCommand = new Command($query, $connection);
            return $tableCommand->ExecuteNonQuery();
        };

        $CreateIndex = function($connection, $exec, $indexName, $table, $fields) {
            $res = $exec('CREATE INDEX `' . $indexName . '` ON `'.$table.'`(`'.implode('`,`', $fields).'`)', $connection);
            return $res->error;
        };

        $DropIndex = function($connection, $exec, $indexName, $table) {
            $res = $exec('DROP INDEX `' . $indexName . '` ON `'.$table.'`', $connection);
            return $res->error;
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

            $error = $CreateIndex($this->_connection, $Exec, $table . '_datecreated_idx', $table, ['datecreated']);
            if ($error) {
                $logger->error($table . ': Can not create index: ' . $error);
                throw new Exception('Can not create index: ' . $error);
            }
            $error = $CreateIndex($this->_connection, $Exec, $table . '_datemodified_idx', $table, ['datemodified']);
            if ($error) {
                $logger->error($table . ': Can not create index: ' . $error);
                throw new Exception('Can not create index: ' . $error);
            }
            $error = $CreateIndex($this->_connection, $Exec, $table . '_datedeleted_idx', $table, ['datedeleted']);
            if ($error) {
                $logger->error($table . ': Can not create index: ' . $error);
                throw new Exception('Can not create index: ' . $error);
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


        $types = $this->_connection->AllowedTypes();
        $hasPrefix = $this->_connection->FieldsHasPrefix();
        $hasMultiFieldIndexes = $this->_connection->HasMultiFieldIndexes();

        $xfields = $xstorage['fields'] ?? [];
        $logger->error($table . ': Checking fields');
        foreach ($xfields as $fieldName => $xfield) {
            $fname = $hasPrefix ? $storage . '_' . $fieldName : $fieldName;

            $typeInfo = $types[$xfield['type']];
            if(isset($typeInfo['db'])) {
                $xfield['type'] = $typeInfo['db'];
            }
            
            $xdesc = isset($xfield['desc']) ? json_encode($xfield['desc'], JSON_UNESCAPED_UNICODE) : '';
            if (!isset($ofields[$fname])) {
                $logger->error($storage . ': ' . $fieldName . ': Field destination not found: creating');

                $res = $Exec('
                    ALTER TABLE `' . $table . '` 
                    ADD COLUMN `' . $fname . '` ' . $xfield['type'], 
                    $this->_connection
                );

                if ($res->error) {
                    $logger->error($table . ': Can not save field: ' . $res->query);
                    throw new Exception('Can not save field: ' . $res->query);
                }

            } else {
                // проверить на соответствие
                $ofield = $ofields[$fname];
                if ($ofield->Type != $xfield['type']) {
                    $logger->error($storage . ': ' . $fieldName . ': Field destination changed: updating');

                    $res = $Exec(
                        'ALTER TABLE `' . $table . '` 
                        DROP COLUMN `' . $fname . '`',
                        $this->_connection
                    );
                    if ($res->error) {
                        $logger->error($table . ': Can not remove field: ' . $res->query);
                        throw new Exception('Can not remove field: ' . $res->query);
                    }

                    $res = $Exec(
                        'ALTER TABLE `' . $table . '` 
                        Add COLUMN `' . $fname . '` ' . $xfield['type'],
                        $this->_connection
                    );
                    if ($res->error) {
                        $logger->error($table . ': Can not add field: ' . $res->query);
                        throw new Exception('Can not add field: ' . $res->query);
                    }

                }
            }
        }


        $xindexes = isset($xstorage['indices']) ? $xstorage['indices'] : [];
        $logger->error($storage . ': Checking indices');
        foreach ($xindexes as $indexName => $xindex) {
            
            $canBeIndexed = true;
            foreach($xindex['fields'] as $field) {
                $xfield = $xfields[$field];
                $typeInfo = $types[$xfield['type']];
                if(!isset($typeInfo['index']) || !$typeInfo['index']) {
                    $canBeIndexed = false;
                    break;
                }
            }

            if(!$hasMultiFieldIndexes && count($xindex['fields']) > 1) {
                $canBeIndexed = false;
            }

            if($canBeIndexed) {

                $xindex['fields'] = array_map(fn ($v) => $hasPrefix ? $storage . '_' . $v : $v, $xindex['fields']);

                if (!isset($indices[$indexName])) {
                    $logger->error($storage . ': ' . $indexName . ': Index not found: creating');
                    $error = $CreateIndex($this->_connection, $Exec, $indexName, $table, $xindex['fields']);
                    if ($error && strstr($error, 'allready exists') !== false) {
                        $DropIndex($this->_connection, $Exec, $indexName, $table);
                        $error = $CreateIndex($this->_connection, $Exec, $indexName, $table, $xindex['fields']);
                        if(!$error) {
                            $logger->error($table . ': Can not create index: ' . $error);
                            throw new Exception('Can not create index: ' . $error);    
                        }
                    } elseif ($error) {
                        $logger->error($table . ': Can not create index: ' . $error);
                        throw new Exception('Can not create index: ' . $error);
                    }
                } else {
                    $oindex = $indices[$indexName];

                    $fields1 = implode(',', $xindex['fields']);
                    $fields2 = implode(',', $oindex->Columns);

                    if($fields1 != $fields2) {
                        $error = $DropIndex($this->_connection, $Exec, $indexName, $table);
                        if ($error) {
                            $logger->error($table . ': Can not drop index: ' . $error);
                            throw new Exception('Can not drop index: ' . $error);
                        }

                        $error = $CreateIndex($this->_connection, $Exec, $indexName, $table, $xindex['fields']);
                        if ($error) {
                            $logger->error($table . ': Can not create index: ' . $error);
                            throw new Exception('Can not create index: ' . $error);
                        }
                    }
                }
            } else {
                $logger->error($table . ': one or more fields can not be indexed. ' . implode(',', $xindex['fields']));
            }

        }
    }


}
