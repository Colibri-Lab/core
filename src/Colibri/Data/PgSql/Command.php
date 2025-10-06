<?php

/**
 * PgSql
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\PgSql
 */

namespace Colibri\Data\PgSql;

use Colibri\Data\SqlClient\Command as SqlCommand;
use Colibri\Data\PgSql\Exception as PgSqlException;
use Colibri\Data\SqlClient\IDataReader;
use Colibri\Data\SqlClient\QueryInfo;
use Colibri\Utils\Logs\Logger;
use PgSql\Result;

/**
 * Class for executing commands at the access point.
 *
 * This class extends SqlCommand and provides methods for preparing and executing queries.
 *
 * @inheritDoc
 *
 */
final class Command extends SqlCommand
{
    /**
     * Prepares the query with parameters.
     *
     * @param string $query The query string with placeholders.
     * @return string The prepared query string with parameters replaced.
     */
    private function _prepareStatement(string $query): string
    {
        $params = $this->_params;
        $query = preg_replace_callback('/\[\[([^\]]+)\]\]/', function ($match) use ($params) {

            $match = $match[1];
            $type = 'string';
            $matching = explode(':', $match);
            if(count($matching) > 1) {
                $type = $matching[1];
            }

            if ($type === 'string') {
                return '\''.($params[$matching[0]] ?? '').'\'';
            } elseif ($type === 'integer') {
                return $params[$matching[0]] ?? 'null';
            } elseif ($type === 'double') {
                return $params[$matching[0]] ?? 'null';
            }

        }, $query);

        return $query;
    }

    /**
     * Executes a query and returns an IDataReader.
     *
     * @param bool $info Whether to execute a query to get the 'affected' variable.
     * @return IDataReader An instance of IDataReader containing the query results.
     */
    public function ExecuteReader(bool $info = true): IDataReader
    {

        // выбираем базу данныx, с которой работает данный линк
        // mysqli_select_db($this->_connection->resource, $this->_connection->database);

        // если нужно посчитать количество результатов
        $affected = null;
        if ($this->page > 0 && $info) {

            // выполняем запрос для получения количества результатов без limit-ов
            $limitQuery = 'select count(*) as affected from (' . $this->query . ') tbl';
            $limitQuery = $this->_prepareStatement($limitQuery);
            $ares = pg_query($this->_connection->resource, $limitQuery);
            if (!($ares instanceof Result)) {
                throw new PgSqlException(pg_last_error(
                    $this->_connection->resource
                ) . ' query: ' . $limitQuery, 500);
            }
            if (pg_num_rows($ares) > 0) {
                $affected = pg_fetch_object($ares)->affected;
            }

        }

        // добавляем к тексту запроса limit-ы
        $preparedQuery = $this->PrepareQueryString();
        $preparedQuery = $this->_prepareStatement($preparedQuery);
        // выполняем запрос
        $res = pg_query($this->connection->resource, $preparedQuery);
        if (!($res instanceof Result)) {
            throw new PgSqlException(pg_last_error($this->_connection->resource) . ' query: ' . $preparedQuery);
        }

        return new DataReader($res, $affected, $preparedQuery);

    }

    /**
     * Executes a query and returns a QueryInfo object.
     *
     * @param string|null $returning (Optional) The column name to return after executing the query.
     * @return QueryInfo A QueryInfo object containing information about the executed query.
     */
    public function ExecuteNonQuery(?string $returning = null): QueryInfo
    {
        $query = $this->_prepareStatement($this->query);
        $res = pg_query($this->_connection->resource, $query . ($returning ? ' returning ' . $returning : ''));
        if(!$res) {
            return new QueryInfo(
                $this->type,
                0,
                0,
                pg_last_error($this->connection->resource),
                $this->query
            );
        }

        $insertId = 0;
        if($returning) {
            $object = pg_fetch_object($res);
            $insertId = $object->$returning;
        }

        return new QueryInfo(
            $this->type,
            $insertId,
            !$res ? 0 : pg_affected_rows($res),
            pg_last_error($this->connection->resource),
            $this->query
        );
    }

    /**
     * Prepares the query string by adding pagination and other necessary modifications for the specific driver.
     *
     * @return string The prepared query string.
     */
    public function PrepareQueryString(): string
    {
        $query = $this->query;
        if ($this->_page > 0) { //  && strstr($query, "limit") === false
            $query .= ' limit ' . $this->_pagesize . ' offset ' . (($this->_page - 1) * $this->_pagesize);
        }
        return $query;
    }

    public function Migrate(Logger $logger, string $storage, array $xstorage): void
    {

        $prefix = isset($xstorage['prefix']) ? $xstorage['prefix'] : '';
        $table = $prefix ? $prefix . '_' . $storage : $storage;

        $queryBuilder = new QueryBuilder($this->_connection);

        $CreateReader = function (string $query, $connection) {
            $tableCommand = new Command($query, $connection);
            return $tableCommand->ExecuteReader();
        };

        $Exec = function (string $query, $connection) {
            $tableCommand = new Command($query, $connection);
            return $tableCommand->ExecuteNonQuery();
        };

        $Exec('CREATE EXTENSION postgis', $this->_connection);
        $Exec('CREATE EXTENSION postgis_topology', $this->_connection);

        $UpdateDefaultAndLength = function (
            string $field,
            string $type,
            bool $required,
            ?int $length,
            mixed $default
        ): array {

            if (\is_bool($default)) {
                $default = $default ? 'TRUE' : 'FALSE';
            }

            if ($type == 'json' || $type == 'jsonb') {
                $default = $default ? '(' . $default . ')' : null;
                $required = false;
                $length = null;
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
            $createCommands = $queryBuilder->CreateDefaultStorageTable($storage, $prefix);
            foreach($createCommands as $query) {
                $res = $Exec($query, $this->_connection);
                if ($res->error) {
                    $logger->error($table . ': Can not create destination: ' . $res->query);
                    throw new Exception('Can not create destination: ' . $res->query);
                }
            }
        }

        try {

            $fieldsReader = $CreateReader($queryBuilder->CreateShowField($table, $this->_connection->database), $this->_connection);
            $ofields = [];
            while($field = $fieldsReader->Read()) {
                $f = self::ExtractFieldInformation($field);
                $ofields[$f->Field] = $f;
            }

            $indexesReader = $CreateReader($queryBuilder->CreateShowIndexes($table), $this->_connection);
            $indices = [];
            while ($index = $indexesReader->Read()) {
                $i = self::ExtractIndexInformation($index);
                $indices[$i->Name] = $i;
            }

            $triggersReader = $CreateReader($queryBuilder->CreateShowTriggers($table, $this->_connection->database), $this->_connection);
            $triggers = [];
            while ($trigger = $triggersReader->Read()) {
                $triggers[$trigger->trigger_name] = $trigger;
            }

        } catch(\Throwable $e) {
            $logger->error($table . ' does not exists');
        }

        $virutalFields = [];
        $types = Config::AllowedTypes();

        $xfields = $xstorage['fields'] ?? [];
        $logger->error($table . ': Checking fields');
        foreach ($xfields as $fieldName => $xfield) {
            $fname = $storage . '_' . $fieldName;
            $fparams = $xfield['params'] ?? [];

            $typeInfo = $types[$xfield['type']];
            if(isset($typeInfo['db'])) {
                $xfield['type'] = $typeInfo['db'];
            }

            if (($xfield['virtual'] ?? false) === true) {
                $virutalFields[$fieldName] = $xfield;
                continue;
            }

            if ($xfield['type'] === 'bool' || $xfield['type'] === 'boolean') {
                $xfield['type'] = 'int2';
                $xfield['length'] = null;
                if(isset($xfield['default'])) {
                    $xfield['default'] = $xfield['default'] === 'true' ? 1 : 0;
                }
            } elseif ($xfield['type'] === 'json' || $xfield['type'] === 'jsonb') {
                $fparams['required'] = false;
                $xfield['length'] = null;
            }

            $xdesc = isset($xfield['desc']) ? json_encode($xfield['desc'], JSON_UNESCAPED_UNICODE) : '';
            if (!isset($ofields[$fname])) {
                $logger->error($storage . ': ' . $fieldName . ': Field destination not found: creating');

                $length = isset($xfield['length']) ? $xfield['length'] : null;
                $default = isset($xfield['default']) ? $xfield['default'] : null;
                $required = isset($fparams['required']) ? $fparams['required'] : false;
                $type = $xfield['type'];

                [$required, $length, $default] = $UpdateDefaultAndLength($fieldName, $type, $required, $length, $default);

                $res = $Exec(
                    '
                    ALTER TABLE "' . $table . '" 
                    ADD COLUMN "' . $fname . '" ' . $type . ($length ? '(' . $length . ')' : '') . ($required ? ' NOT NULL' : ' NULL') . ' 
                    ' . ($default ? 'DEFAULT ' . $default . ' ' : ''),
                    $this->_connection
                );

                if ($res->error) {
                    $logger->error($table . ': Can not save field: ' . $res->query);
                    throw new Exception('Can not save field: ' . $res->query);
                }

                if($xdesc) {
                    $res = $Exec('COMMENT ON COLUMN "' . ($prefix ? $prefix . '_' : '') . $table . '"."' . $fname . '" IS \'' . str_replace("'", "`", $xdesc) . '\';', $this->_connection);
                    if ($res->error) {
                        $logger->error($table . ': Can not save field: ' . $res->query);
                        throw new Exception('Can not save field: ' . $res->query);
                    }
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

                    [$required, $length, $default] = $UpdateDefaultAndLength($fieldName, $type, $required, $length, $default);

                    $res = $Exec(
                        'ALTER TABLE "' . $table . '" 
                        ALTER COLUMN "' . $fname . '" TYPE ' . $type . ($length ? '(' . $length . ')' : '') . ' USING "' . $fname . '"::' . $type,
                        $this->_connection
                    );
                    if ($res->error) {
                        $logger->error($table . ': Can not save field: ' . $res->query);
                        throw new Exception('Can not save field: ' . $res->query);
                    }

                    $res = $Exec(
                        'ALTER TABLE "' . $table . '" 
                        ALTER COLUMN "' . $fname . '" '.($required ? 'SET' : 'DROP').' NOT NULL',
                        $this->_connection
                    );
                    if ($res->error) {
                        $logger->error($table . ': Can not save field: ' . $res->query);
                        throw new Exception('Can not save field: ' . $res->query);
                    }

                    $res = $Exec(
                        'ALTER TABLE "' . $table . '" 
                        ALTER COLUMN "' . $fname . '" '.(!is_null($default) ? 'SET DEFAULT ' . $default : 'DROP DEFAULT'),
                        $this->_connection
                    );
                    if ($res->error) {
                        $logger->error($table . ': Can not save field: ' . $res->query);
                        throw new Exception('Can not save field: ' . $res->query);
                    }

                    if($xdesc) {
                        $res = $Exec('COMMENT ON COLUMN "' . $table . '"."' . $fname . '" IS \'' . str_replace("'", "`", $xdesc) . '\';', $this->_connection);
                        if ($res->error) {
                            $logger->error($table . ': Can not save field: ' . $res->query);
                            throw new Exception('Can not save field: ' . $res->query);
                        }
                    }


                }
            }
        }

        foreach ($virutalFields as $fieldName => $xVirtualField) {
            $fname = $storage . '_' . $fieldName;
            $fparams = $xVirtualField['params'] ?? [];

            $typeInfo = $types[$xVirtualField['type']];
            if(!$typeInfo) {
                foreach($types as $tname => $tinfo) {
                    if($tinfo['db'] === $xVirtualField['type']) {
                        $typeInfo = $tinfo;
                        break;
                    }
                }
            }
            if(isset($typeInfo['db'])) {
                $xVirtualField['type'] = $typeInfo['db'];
            }

            $xdesc = isset($xVirtualField['desc']) ? json_encode($xVirtualField['desc'], JSON_UNESCAPED_UNICODE) : '';
            $length = isset($xVirtualField['length']) ? $xVirtualField['length'] : null;
            
            if (!isset($ofields[$fname])) {
                $res = $Exec('
                    ALTER TABLE "' . $table . '" 
                    ADD COLUMN "' . $fname . '" ' . $xVirtualField['type'] . ($length ? '(' . $length . ')' : '') . ' 
                    GENERATED ALWAYS AS (' . $xVirtualField['expression'] . ') STORED ', $this->_connection);

                if ($res->error) {
                    $logger->error($table . ': Can not save field: ' . $res->query);
                    throw new Exception('Can not save field: ' . $res->query);
                }

                if($xdesc) {
                    $res = $Exec('COMMENT ON COLUMN "' . $table . '"."' . $fname . '" IS \'' . str_replace("'", "`", $xdesc) . '\';', $this->_connection);
                    if ($res->error) {
                        $logger->error($table . ': Can not save field: ' . $res->query);
                        throw new Exception('Can not save field: ' . $res->query);
                    }
                }


            } else {
                $ofield = $ofields[$fname];

                $required = isset($fparams['required']) ? $fparams['required'] : false;
                $expression = isset($xVirtualField['expression']) ? $xVirtualField['expression'] : null;

                $orType = strtolower($ofield->Type) != strtolower($xVirtualField['type']) . ($length ? '(' . $length . ')' : '');
                $orExpression = strtolower($ofield->Expression) != strtolower($expression);
                $orRequired = $required != ($ofield->Null == 'NO');

                if ($orType || $orExpression || $orRequired) {
                    $logger->error($storage . ': ' . $fieldName . ': Field destination changed: updating');

                    $length = isset($xVirtualField['length']) ? $xVirtualField['length'] : null;

                    $res = $Exec(
                        'ALTER TABLE "' . $table . '" 
                        DROP COLUMN "' . $fname . '"',
                        $this->_connection
                    );
                    $res = $Exec('
                        ALTER TABLE "' . $table . '" 
                        ADD COLUMN "' . $fname . '" ' . $xVirtualField['type'] . ($length ? '(' . $length . ')' : '') . ' 
                        GENERATED ALWAYS AS (' . $xVirtualField['expression'] . ') STORED 
                    ', $this->_connection);
                    if ($res->error) {
                        $logger->error($table . ': Can not save field: ' . $res->query);
                        throw new Exception('Can not save field: ' . $res->query);
                    }

                    if($xdesc) {
                        $res = $Exec('COMMENT ON COLUMN "' . $table . '"."' . $fname . '" IS \'' . str_replace("'", "`", $xdesc) . '\';', $this->_connection);
                        if ($res->error) {
                            $logger->error($table . ': Can not save field: ' . $res->query);
                            throw new Exception('Can not save field: ' . $res->query);
                        }
                    }

                }

            }
        }

        $createIndex = function ($Exec, $connection, $indexName, $prefix, $table, $type, $method, $xindex) {
            return $Exec('
                CREATE '.($type === 'NORMAL' ? '' : $type).' INDEX "' . $indexName . '" ON "' . ($prefix ? $prefix . '_' : '') . $table . '" '.($method ? 'USING ' . $method : '') . ' (
                    "' . $table . '_' . implode('","' . $table . '_', $xindex['fields']) . '"
                )
            ', $connection);
        };

        $dropIndex = function ($Exec, $connection, $indexName) {
            return $Exec('DROP INDEX IF EXISTS "' . $indexName . '"', $connection);
        };

        $xindexes = isset($xstorage['indices']) ? $xstorage['indices'] : [];
        $logger->error($storage . ': Checking indices');
        foreach ($xindexes as $indexName => $xindex) {
            if (!isset($indices[$indexName])) {
                $logger->error($storage . ': ' . $indexName . ': Index not found: creating');

                $type = isset($xindex['type']) ? $xindex['type'] : 'NORMAL';
                $method = isset($xindex['method']) ? $xindex['method'] : 'BTREE';

                $res = $createIndex($Exec, $this->_connection, $indexName, $prefix, $storage, $type, $method, $xindex);
                if ($res->error && strstr($res->error, 'already exists') !== false) {
                    $res = $dropIndex($Exec, $this->_connection, $indexName);
                    if ($res->error) {
                        $logger->error($table . ': Can not delete index: ' . $res->query);
                        throw new Exception('Can not delete index: ' . $res->query);
                    }

                    $res = $createIndex($Exec, $this->_connection, $indexName, $prefix, $storage, $type, $method, $xindex);
                    if ($res->error) {
                        $logger->error($table . ': Can not create index: ' . $res->query);
                        throw new Exception('Can not create index: ' . $res->query);
                    }
                }

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

                $otype = 'NORMAL';
                $omethod = 'BTREE';
                if ($oindex->NonUnique == 0) {
                    $otype = 'UNIQUE';
                    $omethod = $oindex->Type;
                }

                if ($fields1 != $fields2 || $xtype != $otype || $xmethod != $omethod) {
                    $logger->error($storage . ': ' . $indexName . ': Index changed: updating');

                    $res = $dropIndex($Exec, $this->_connection, $indexName);
                    if ($res->error) {
                        $logger->error($table . ': Can not delete index: ' . $res->query);
                        throw new Exception('Can not delete index: ' . $res->query);
                    }

                    $res = $createIndex($Exec, $this->_connection, $indexName, $prefix, $storage, $xtype, $xmethod, $xindex);
                    if ($res->error) {
                        $logger->error($table . ': Can not create index: ' . $res->query);
                        throw new Exception('Can not create index: ' . $res->query);
                    }

                }
            }
        }

        $createTrigger = function ($Exec, $connection, $triggerName, $prefix, $table, $type, $code, $xtrigger) {
            $Exec('
                CREATE OR REPLACE FUNCTION '.$triggerName.'()
                RETURNS TRIGGER AS $$
                BEGIN
                    '.$code.'
                END;
                $$ LANGUAGE plpgsql;
            ', $connection);
            $Exec('
                DROP TRIGGER IF EXISTS '.$triggerName.' ON ' . $table . ';
            ', $connection);
            return $Exec('
                CREATE TRIGGER '.$triggerName.'
                '.$type.' ON '.$table.'
                FOR EACH ROW
                EXECUTE FUNCTION '.$triggerName.'();
            ', $connection);
        };

        $dropTrigger = function ($Exec, $connection, $triggerName, $table) {
            return $Exec('DROP TRIGGER IF EXISTS "' . $triggerName . '" ON  ON "' . $table . '"', $connection);
        };

        $xtriggers = isset($xstorage['triggers']) ? $xstorage['triggers'] : [];
        $logger->error($storage . ': Creating triggers');
        foreach ($xtriggers as $triggerName => $xtrigger) {
            if (!isset($triggers[$triggerName])) {
                $logger->error($storage . ': ' . $triggerName . ': Trigger not found: creating');

                $type = isset($xtrigger['type']) ? $xtrigger['type'] : 'BEFORE INSERT OR UPDATE';
                $code = isset($xtrigger['code']) ? $xtrigger['code'] : '';

                $res = $createTrigger($Exec, $this->_connection, $triggerName, $prefix, $storage, $type, $code, $xtrigger);
                if ($res->error && strstr($res->error, 'already exists') !== false) {
                    $res = $dropTrigger($Exec, $this->_connection, $triggerName, $table);
                    if ($res->error) {
                        $logger->error($table . ': Can not delete trigger: ' . $res->query);
                        throw new Exception('Can not delete trigger: ' . $res->query);
                    }

                    $res = $createTrigger($Exec, $this->_connection, $triggerName, $prefix, $storage, $type, $code, $xtrigger);
                    if ($res->error) {
                        $logger->error($table . ': Can not create trigger: ' . $res->query);
                        throw new Exception('Can not create trigger: ' . $res->query);
                    }
                }

                if ($res->error) {
                    $logger->error($table . ': Can not create trigger: ' . $res->query);
                    throw new Exception('Can not create trigger: ' . $res->query);
                }
            } else {
                $otrigger = $triggers[$triggerName];
                
                $xtype = isset($xtrigger['type']) ? $xtrigger['type'] : 'BEFORE INSERT OR UPDATE';
                $xcode = isset($xindex['code']) ? $xtrigger['code'] : '';

                $otype = $otrigger->when_to_fire;
                $ocode = $otrigger->definition;

                if ($xtype != $otype || $xcode != $ocode) {
                    $logger->error($storage . ': ' . $indexName . ': Trigger changed: updating');

                    $res = $dropTrigger($Exec, $this->_connection, $triggerName, $table);
                    if ($res->error) {
                        $logger->error($table . ': Can not delete trigger: ' . $res->query);
                        throw new Exception('Can not delete trigger: ' . $res->query);
                    }

                    $res = $createTrigger($Exec, $this->_connection, $triggerName, $prefix, $storage, $xtype, $xcode, $xtrigger);
                    if ($res->error) {
                        $logger->error($table . ': Can not create trigger: ' . $res->query);
                        throw new Exception('Can not create trigger: ' . $res->query);
                    }

                }
            }
            

        }

    }


    public static function ExtractFieldInformation(array|object $field): object
    {
        $field = (object)$field;
        return (object) [
            'Field' => $field->column_name,
            'Type' => $field->udt_name . ($field?->character_maximum_length ? '('.$field->character_maximum_length.')' : ''),
            'Null' => $field->is_nullable,
            'Key' => $field->is_identity,
            'Default' => $field->column_default,
            'Expression' => $field->generation_expression ?? ''
        ];
    }

    public static function ExtractIndexInformation(array|object $index): object
    {
        $def = strtolower($index->indexdef);
        preg_match('/\((.*)\)/i', $def, $matches);
        $colList = $matches[1];
        $columns = array_map(fn ($v) => trim($v), explode(',', $colList));
        return (object)[
            'Name' => $index->indexname,
            'Columns' => $columns,
            'ColumnPosition' => 1,
            'Collation' => 'utf8',
            'Null' => 'YES',
            'NonUnique' => strstr($def, 'unique') === false ? 1 : 0,
            'Type' => 'BTREE',
            'Primary' => strstr($index->indexname, '_pkey') !== false
        ];



    }

}
