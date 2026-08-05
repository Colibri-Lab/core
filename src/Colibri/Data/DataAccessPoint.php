<?php

/**
 * Data
 *
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Data
 */

namespace Colibri\Data;

use Colibri\App;
use Colibri\Data\NoSqlClient\ICommandResult;
use Colibri\Data\SqlClient\IConnection as ISqlClientConnection;
use Colibri\Data\NoSqlClient\IConnection as INoSqlClientConnection;
use Colibri\Data\SqlClient\IDataReader;
use Colibri\Data\SqlClient\QueryInfo;
use Colibri\Data\Storages\Storage;
use Colibri\Utils\Debug;
use Colibri\Utils\Logs\Logger;
use DateTime;

/**
 * Access Point
 * @example
 * ```
 *
 * $accessPoint = App::$dataAccessPoints->Get('main');
 *
 * # Retrieving data by query
 *
 * class Queries {
 *     const TestSelectQuery = '
 *            select *
 *            from test
 *            where id=[[id:integer]] and text=[[text:string]] and dbl=[[dbl::double]]';
 * }
 * $reader = $accessPoint->Query(
 *             Queries::TestSelectQuery, [
 *                 'page' => 1, 'pagesize' => 10, 'params' => [
 *                     'id' => 1, 'text' => 'adfadf', 'dbl' => 1.1
 *                 ]
 *             ]);
 * while($result = $reader->Read()) {
 *     print_r($result); // object
 * }
 *
 * # or without parameters
 *
 * $reader = $accessPoint->Query('
 *     select *
 *     from test
 *     where id=\'2\' and text=\'adfasdfasdf\' and dbl=\'1.1\'', ['page' => 1, 'pagesize' => 10]);
 * while($result = $reader->Read()) {
 *     print_r($result); // object
 * }
 *
 * $accessPoint->Query('BEGIN');
 *
 * # If you need to execute an insert, update, or delete query
 * $nonQueryInfo = $accessPoint->Query('
 *     delete from test where id=1', ['type' => DataAccessPoint::QueryTypeNonInfo]);
 *
 * # If you need to execute a query with a large amount of data, for example, for queries with auto-fetching
 * $reader = $accessPoint->Query('
 *     select * from test', ['page' => 1, 'pagesize' => 100, 'type' => DataAccessPoint::QueryTypeBigData]);
 *
 * # Input data
 * $nonQueryInfo = $accessPoint->Insert('test', [
 *     'text' => 'адфасдфасдфасдф', 'dbl' => 1.1], 'id'); # only for postgresql
 * # It returns a QueryInfo class, for postgres, an additional parameter returning is required -
 *     the name of the field to return
 *
 * # Data update
 * $returnsBool = $accessPoint->Update('test', ['text' => 'adfasdfasdf', 'dbl' => 1.2], 'id=1');
 * # Returns true if the update was successful
 *
 * # Input with data update, if there is a duplicate on the identity field or sequence for postgresql
 * $nonQueryInfo = $accessPoint->InsertOrUpdate('test', [
 *     'id' => 1, 'text' => 'adfadsfads', 'dbl' => 1.1], ['id', 'text'], 'id');
 * # The returning field is only needed for postgres
 * # It returns a QueryInfo class, for postgres, an additional parameter returning is required -
 *     the name of the field to return
 *
 * # Batch data input
 * $nonQueryInfo = $accessPoint->InsertBatch('test', [ [
 *     'text' => 'adsfasdf', 'dbl' => 1.0], ['text' => 'adsfasdf', 'dbl' => 1.1] ]);
 *
 * $accessPoint->Query('COMMIT');
 *
 * # Data deletion
 * $returnsBool = $accessPoint->Delete('test', 'id=1');
 * # Returns true if the deletion was successful, note that if
 *     you do not pass the condition parameter, the table test will be truncated
 *
 * # Getting a list of tables
 * $tablesReader = $accessPoint->Tables();
 * # Returns an IDataReader
 *
 * ```
 * 
 * @class
 *
 * @property-read string $name Name of the access point
 * @property-read string $dbms Type of DBMS (relational or nosql)
 * @property-read array $allowedTypes Allowed types of data storage (relational or nosql)
 * @property-read bool $hasIndexes Indicates whether the database supports indexes
 * @property-read bool $fieldsHasPrefix Indicates whether the database fields have a prefix
 * @property-read bool $hasVirtual Indicates whether the database supports virtual fields
 * @property-read bool $hasMultiFieldIndexes Indicates whether the database supports multi-field indexes
 * @property-read bool $hasAutoincrement Indicates whether the database supports auto-increment fields
 * @property-read array $indexTypes Types of indexes supported by the database
 * @property-read array $indexMethods Methods of indexes supported by the database
 * @property-read array $jsonIndexes JSON indexes supported by the database
 * @property-read ISqlClientConnection|INoSqlClientConnection $connection Connection object for interacting with the database
 * @property-read object $point Connection properties object
 * @property-read string $symbol Symbol used for quoting identifiers in the database
 * @property-read bool $hasTriggers Indicates whether the database supports triggers
 *
 */
class DataAccessPoint
{
    /**
     * Indicates whether the connection is established.
     * @var bool
     * @private
     */
    private bool $_connected = false;

    /** 
     * Type of DBMS is relational
     * @const string 
     * @public
     */
    public const DBMSTypeRelational = 'relational';

    /** 
     * Type of DBMS is nosql
     * @const string
     * @public
     */
    public const DBMSTypeNoSql = 'nosql';

    /** 
     * Execute the query and return a Reader
     * @const string 
     * @public
     */
    public const QueryTypeReader = 'reader';

    /** 
     * Execute the query and return a Reader, but without counting the total number of rows.
     * @const string
     * @public
     */
    public const QueryTypeBigData = 'bigdata';

    /** 
     * Execute a query that does not involve reading data.
     * @const string
     * @public
     */
    public const QueryTypeNonInfo = 'noninfo';

    /** 
     * Readonly transation
     * @const string
     * @public
     */
    public const TransationReadonly = 'readonly';

    /** 
     * ReadWrite transaction
     * @const string
     * @public
     */
    public const TransationReadWrite = 'readwrite';

    /**
     * Connection properties
     *
     * @var object
     * @private
     */
    private object $_accessPointData;

    /**
     * Connection object
     *
     * @var ISqlClientConnection|INoSqlClientConnection
     * @private
     */
    private ISqlClientConnection|INoSqlClientConnection $_connection;

    /**
     * Constructor
     *
     * @param object|array $accessPointData The access point data object.
     * @public
     * @constructor
     */
    public function __construct(object|array $accessPointData)
    {

        $this->_accessPointData = (object)$accessPointData;

        $connectionClassObject = $this->_accessPointData->driver->connection;

        $this->_connection = $connectionClassObject::FromConnectionInfo($this->_accessPointData);
        $this->_connected = false;
        // $this->_connection->Open();

    }

    /**
     * Magic get method
     *
     * @param string $property property connection,point or table name
     * @return mixed 
     * @magic
     * @public
     */
    public function __get($property)
    {
        if ($property == 'connection') {
            return $this->_connection;
        } elseif ($property == 'point') {
            return $this->_accessPointData;
        } elseif ($property == 'symbol') {
            $connectionClass = $this->_accessPointData->driver->config;
            return $connectionClass::Symbol();
        } elseif ($property == 'dbms') {
            $connectionClass = $this->_accessPointData->driver->config;
            return $connectionClass::DbmsType();
        } elseif ($property == 'allowedTypes') {
            $connectionClass = $this->_accessPointData->driver->config;
            return $connectionClass::AllowedTypes();
        } elseif ($property == 'hasIndexes') {
            $connectionClass = $this->_accessPointData->driver->config;
            return $connectionClass::HasIndexes();
        } elseif ($property == 'hasTriggers') {
            $connectionClass = $this->_accessPointData->driver->config;
            return $connectionClass::HasTriggers();
        } elseif ($property == 'fieldsHasPrefix') {
            $connectionClass = $this->_accessPointData->driver->config;
            return $connectionClass::FieldsHasPrefix();
        } elseif ($property == 'hasVirtual') {
            $connectionClass = $this->_accessPointData->driver->config;
            return $connectionClass::HasVirtual();
        } elseif ($property == 'hasMultiFieldIndexes') {
            $connectionClass = $this->_accessPointData->driver->config;
            return $connectionClass::HasMultiFieldIndexes();
        } elseif ($property == 'hasAutoincrement') {
            $connectionClass = $this->_accessPointData->driver->config;
            return $connectionClass::HasAutoincrement();
        } elseif ($property == 'indexTypes') {
            $connectionClass = $this->_accessPointData->driver->config;
            return $connectionClass::IndexTypes();
        } elseif ($property == 'indexMethods') {
            $connectionClass = $this->_accessPointData->driver->config;
            return $connectionClass::IndexMethods();
        } elseif ($property == 'jsonIndexes') {
            $connectionClass = $this->_accessPointData->driver->config;
            return $connectionClass::JsonIndexes();
        } else {
            if($this->dbms === self::DBMSTypeRelational) {
                return $this->Query('select * from ' . $property);
            } else {
                throw new DataAccessPointsException('Can not execute relational query on nosql database');
            }
        }
    }

    /**
     * Reopens the connection to the database.
     *
     * @return void
     * @public
     * @example
     * ```
     * $accessPoint = App::$dataAccessPoints->Get('main');
     * $accessPoint->Reopen();
     * ```
     */
    public function Reopen()
    {
        $this->_connection->Reopen();
    }

    /**
     * Executes a command in the access point.
     *
     * @param string $command The command to execute.
     * @param mixed ...$arguments The arguments for the command.
     * @return mixed The result of the command execution.
     * @throws DataAccessPointsException If the command is not found in the driver Command object.
     * @public
     * @example
     * ```
     * $accessPoint = App::$dataAccessPoints->Get('main');
     * $accessPoint->ExecuteCommand('SomeCommand', 'arg1', 'arg2');
     * ```
     */
    public function ExecuteCommand(string $command, ...$arguments): mixed
    {
        if(!$this->_connected) {
            $this->_connection->Open();
            $this->_connected = true;
        }
        $configClassObject = $this->_accessPointData->driver->config;
        $commandClassObject = $this->_accessPointData->driver->command;
        if($configClassObject::DbmsType() === self::DBMSTypeRelational) {
            $cmd = new $commandClassObject('', $this->_connection);
        } else {
            $cmd = new $commandClassObject($this->_connection);
        }
        if(method_exists($cmd, $command)) {
            return $cmd->$command(...$arguments);
        }

        throw new DataAccessPointsException('Can not find required command in driver Command object');

    }

    /**
     * Calls the command in Command object if the method does not exists in DataAccessPoint class.
     *
     * @return void
     * @public
     * @magic
     * @example
     * ```
     * $accessPoint = App::$dataAccessPoints->Get('main');
     * $accessPoint->SomeCommand('arg1', 'arg2');
     * ```
     */
    public function __call(string $name, array $arguments): mixed
    {
        if(!$this->_connected) {
            $this->_connection->Open();
            $this->_connected = true;
        }
        if(method_exists($this, $name)) {
            return $this->$name(...$arguments);
        }
        return $this->ExecuteCommand($name, ...$arguments);
    }

    /**
     * Executes a query in the access point.
     *
     * @example
     * ```
     * $accessPoint = App::$dataAccessPoints->Get('main');
     * $reader = $accessPoint->Query('select * from test where id=[[id:integer]]', ['page' => 1, 'pagesize' => 10, 'params' => ['id' => 1]]);
     * while($result = $reader->Read()) {
     *     print_r($result); // object
     * }
     * $nonQueryInfo = $accessPoint->Query('delete from test where id=[[id:integer]]', ['type' => DataAccessPoint::QueryTypeNonInfo, 'params' => ['id' => 1]]);
     * $reader = $accessPoint->Query('select * from test', ['page' => 1, 'pagesize' => 100, 'type' => DataAccessPoint::QueryTypeBigData]);  
     * ```
     *
     * @param string $query The query string.
     * @param object|array $commandParams [
     *                          page, pagesize, params, type = bigdata|noninfo|reader (default reader),
     *                          returning = ''
     *                     ]
     * @return IDataReader|QueryInfo|null Returns an IDataReader object, a QueryInfo object, or null.
     * @public
     */
    public function Query($query, $commandParams = []): IDataReader|QueryInfo
    {
        if(!$this->_connected) {
            $this->_connection->Open();
            $this->_connected = true;
        }

        // Превращаем параметры в обьект
        $commandParams = (object) $commandParams;

        $commandClassObject = $this->_accessPointData->driver->command;
        $cmd = new $commandClassObject($query, $this->_connection);

        if (isset($commandParams->page)) {
            $cmd->page = $commandParams->page;
            $cmd->pagesize = isset($commandParams->pagesize) ? $commandParams->pagesize : 10;
        }

        if (isset($commandParams->params)) {
            $cmd->params = (array) $commandParams->params;
        }

        if (!isset($commandParams->type)) {
            $commandParams->type = self::QueryTypeBigData;
        }

        $queryStartTime = new DateTime();

        try {
            if ($commandParams->type == self::QueryTypeReader) {
                $return = $cmd->ExecuteReader();
            } elseif ($commandParams->type == self::QueryTypeBigData) {
                $return = $cmd->ExecuteReader(false);
            } elseif ($commandParams->type == self::QueryTypeNonInfo) {
                $return = $cmd->ExecuteNonQuery(isset($commandParams->returning) ? $commandParams->returning : '');
            } else {
                $return = new QueryInfo($cmd->type, 0, 0, 'Unknown command type: ' . $commandParams->type, $cmd->query);
            }

        } catch(\Throwable $e) {
            $return = new QueryInfo($cmd->type, 0, 0, $e->getMessage(), $cmd->query);
        }

        $logSetting = $this->_accessPointData->logqueries ?? [];
        $minDelay = $this->_accessPointData->mindelay ?? 0;
        if (!empty($logSetting)) {
            $diff = $queryStartTime->diff(new DateTime());
            $delay = ($diff->format('%f') / 1000);
            if($delay > $minDelay) {
                if(in_array('text', $logSetting)) {
                    app_debug('Query: ' . $delay . ' ms: ' .
                        str_replace("\r", " ", str_replace("\n", " ", $query)) .
                        ' (' . $cmd->page . ', ' . $cmd->pagesize . ') - ' .
                        ', Type: ' . $commandParams->type);
                }
                if(in_array('params', $logSetting)) {
                    app_debug($commandParams);
                }
                if(in_array('return', $logSetting)) {
                    app_debug($return);
                }
                app_debug('--------');
            }

        }

        return $return;

    }

    /**
     * Creates a query using the query builder.
     *
     * @param string $method The method name of the query builder.
     * @param array $attributes The attributes to pass to the query builder method.
     * @return mixed The result of the query builder method.
     * @public
     * @example
     * ```
     * $accessPoint = App::$dataAccessPoints->Get('main');
     * ```
     */
    public function CreateQuery(string $method, array $attributes)
    {
        if(!$this->_connected) {
            $this->_connection->Open();
            $this->_connected = true;
        }

        $querybuilderClassObject = $this->_accessPointData->driver->querybuilder;
        $queryBuilder = new $querybuilderClassObject($this->_connection);
        return $queryBuilder->$method(...$attributes);
    }

    /**
     * Get status.
     *
     * @param string $table The name of the table.
     * @return IDataReader|QueryInfo
     * @public
     * @example
     * ```
     * $accessPoint = App::$dataAccessPoints->Get('main');
     * $status = $accessPoint->Status('my_table');
     * ```
     */
    public function Status(string $table): IDataReader|QueryInfo
    {
        if(!$this->_connected) {
            $this->_connection->Open();
            $this->_connected = true;
        }
        return $this->Query($this->CreateQuery('CreateShowStatus', [$table]));
    }

    /**
     * Inserts a new row.
     *
     * @param string $table The name of the table.
     * @param array $row The row to be inserted.
     * @param string $returning The name of the field whose value needs to be returned. (For MySQL, this can be omitted, and the value of the identity field will be returned.)
     * @return QueryInfo
     * @public
     * @example
     * ```
     * $accessPoint = App::$dataAccessPoints->Get('main');
     * $queryInfo = $accessPoint->Insert('my_table', ['field1' => 'value1'], 'id');
     * ```
     */
    public function Insert(string $table, array $row = [], string $returning = '', ?array $params = null): QueryInfo
    {
        if(!$this->_connected) {
            $this->_connection->Open();
            $this->_connected = true;
        }
        $queryParams = ['type' => self::QueryTypeNonInfo, 'returning' => $returning];
        if (!is_null($params)) {
            $queryParams['params'] = $params;
        }
        return $this->Query($this->CreateQuery('CreateInsert', [$table, $row]), $queryParams);
    }

    /**
     * Inserts a new row or updates an existing one if index fields match.
     * A great way to avoid worrying about whether a row exists in the database or not.
     * Works slower than regular data insertion, so use with caution.
     *
     * @param string $table The table.
     * @param array $row The row to be inserted.
     * @param array $exceptFields Which fields to exclude from updating if the row exists based on index fields.
     * @param string $returning The name of the field whose value needs to be returned. (For MySQL, this can be omitted, and the value of the identity field will be returned.)
     * @return QueryInfo
     * @public
     * @example
     * ```
     * $accessPoint = App::$dataAccessPoints->Get('main');
     * $queryInfo = $accessPoint->InsertOrUpdate('my_table', ['field1' => 'value1'], ['field2'], 'id');
     * ```
     */
    public function InsertOrUpdate(
        string $table,
        array $row = [],
        array $exceptFields = [],
        string $returning = '' /* used only in postgres*/
    ): QueryInfo {
        if(!$this->_connected) {
            $this->_connection->Open();
            $this->_connected = true;
        }
        return $this->Query($this->CreateQuery('CreateInsertOrUpdate', [
            $table,
            $row,
            $exceptFields
        ]), ['type' => self::QueryTypeNonInfo, 'returning' => $returning]);
    }

    /**
     * Inserts multiple rows at once.
     *
     * @param string $table The table.
     * @param array $rows The rows to be inserted.
     * @return QueryInfo
     * @public
     * @example
     * ```
     * $accessPoint = App::$dataAccessPoints->Get('main');
     * $queryInfo = $accessPoint->InsertBatch('my_table', [
     *     ['field1' => 'value1'],
     *     ['field1' => 'value2']
     * ]);
     * ```
     */
    public function InsertBatch(string $table, array $rows = []): QueryInfo
    {
        if(!$this->_connected) {
            $this->_connection->Open();
            $this->_connected = true;
        }
        return $this->Query($this->CreateQuery('CreateBatchInsert', [$table, $rows]), ['type' => self::QueryTypeNonInfo]);
    }

    /**
     * Updates a row.
     *
     * @param string $table The table.
     * @param array $row The row to be updated.
     * @param string $condition The update condition.
     * @return QueryInfo|null
     * @public
     * @example
     * ```
     * $accessPoint = App::$dataAccessPoints->Get('main');  
     * $queryInfo = $accessPoint->Update('my_table', ['field1' => 'new_value'], 'id=1');
     * ```
     */
    public function Update(string $table, array $row, string $condition, ?array $params = null): QueryInfo
    {
        if(!$this->_connected) {
            $this->_connection->Open();
            $this->_connected = true;
        }
        $queryParams = ['type' => self::QueryTypeNonInfo];
        if (!is_null($params)) {
            $queryParams['params'] = $params;
        }
        return $this->Query($this->CreateQuery('CreateUpdate', [$table, $condition, $row]), $queryParams);
    }

    /**
     * Deletes a row based on criteria.
     *
     * @param string $table The table.
     * @param string $condition The condition.
     * @return QueryInfo
     * @public
     * @example
     * ```
     * $accessPoint = App::$dataAccessPoints->Get('main');
     * $queryInfo = $accessPoint->Delete('my_table', 'id=1');
     * ```
     */
    public function Delete(string $table, string $condition = ''): QueryInfo
    {
        if(!$this->_connected) {
            $this->_connection->Open();
            $this->_connected = true;
        }
        return $this->Query($this->CreateQuery('CreateDelete', [$table, $condition]), ['type' => self::QueryTypeNonInfo]);
    }

    /**
     * Returns a list of tables in the database.
     *
     * @return IDataReader|null Returns an IDataReader object or null.
     * @public
     * @example
     * ```
     * $accessPoint = App::$dataAccessPoints->Get('main');
     * $tablesReader = $accessPoint->Tables();
     * ```
     */
    public function Tables(?string $table = null): IDataReader|QueryInfo
    {
        if(!$this->_connected) {
            $this->_connection->Open();
            $this->_connected = true;
        }
        return $this->Query($this->CreateQuery('CreateShowTables', [$table]), ['type' => self::QueryTypeReader]);
    }

    /**
     * Returns a list of fields in the database table.
     *
     * @return array Returns an IDataReader object or null.
     * @public
     * @example
     * ```
     * $accessPoint = App::$dataAccessPoints->Get('main');
     * $fields = $accessPoint->Fields('my_table');
     * ```
     */
    public function Fields(string $table, ?string $database = null): array
    {
        if(!$this->_connected) {
            $this->_connection->Open();
            $this->_connected = true;
        }
        $fields = [];
        $return = $this->Query($this->CreateQuery('CreateShowField', [$table, $database ?: $this->point->database]), ['type' => self::QueryTypeReader]);
        while($field = $return->Read()) {
            $configClass = $this->_accessPointData->driver->config;
            $f = $configClass::ExtractFieldInformation($field);
            $fields[$f->Field] = $f;
        }
        return $fields;
    }

    /**
     * Returns a list of indexes in the database table.
     *
     * @return array Returns an IDataReader object or null.
     * @public
     * @example
     * ```
     * $accessPoint = App::$dataAccessPoints->Get('main');
     * $indexes = $accessPoint->Indexes('my_table');
     * ```
     */
    public function Indexes(string $table, ?string $database = null): array
    {
        if(!$this->_connected) {
            $this->_connection->Open();
            $this->_connected = true;
        }
        $return = $this->Query($this->CreateQuery('CreateShowIndexes', [$table, $database ?: $this->point->database]), ['type' => self::QueryTypeReader]);
        $indices = [];
        while ($index = $return->Read()) {
            $configClass = $this->_accessPointData->driver->config;
            $i = $configClass::ExtractIndexInformation($index);

            if (!isset($indices[$i->Name])) {
                $i->Columns = [($i->ColumnPosition - 1) => $i->Columns[0]];
                $indices[$i->Name] = $i;
            } else {
                $indices[$i->Name]->Columns[$i->ColumnPosition - 1] = $i->Columns[0];
            }

        }
        return $indices;
    }

    /**
     * Starts a transaction.
     * @return void
     * @public
     * @example
     * ```
     * $accessPoint = App::$dataAccessPoints->Get('main');
     * $queryInfo = $accessPoint->Begin();
     * ```
     */
    public function Begin(?string $type = null): ?QueryInfo
    {
        if(!$this->_connected) {
            $this->_connection->Open();
            $this->_connected = true;
        }
        if($this->dbms === self::DBMSTypeRelational) {
            return $this->Query($this->CreateQuery('CreateBegin', [$type]), ['type' => DataAccessPoint::QueryTypeNonInfo]);
        }
        return null;
    }

    /**
     * Commits the transaction.
     * @return void
     * @public
     * @example
     * ```
     * $accessPoint = App::$dataAccessPoints->Get('main');
     * $queryInfo = $accessPoint->Commit();
     * ```
     */
    public function Commit(): ?QueryInfo
    {
        if(!$this->_connected) {
            $this->_connection->Open();
            $this->_connected = true;
        }
        if($this->dbms === self::DBMSTypeRelational) {
            return $this->Query($this->CreateQuery('CreateCommit', []), ['type' => DataAccessPoint::QueryTypeNonInfo]);
        }
        return null;
    }

    /**
     * Rolls back the transaction.
     * @return void
     * @public
     * @example 
     * ```
     * $accessPoint = App::$dataAccessPoints->Get('main');
     * $queryInfo = $accessPoint->Rollback();
     * ```
     */
    public function Rollback(): ?QueryInfo
    {
        if(!$this->_connected) {
            $this->_connection->Open();
            $this->_connected = true;
        }
        if($this->dbms === self::DBMSTypeRelational) {
            return $this->Query($this->CreateQuery('CreateRollback', []), ['type' => DataAccessPoint::QueryTypeNonInfo]);
        }
        return null;
    }

    /**
     * Creates a query for a specific field in a table.
     *
     * @param string $field The field name.
     * @param string $table The table name.
     * @return string The generated query string.
     * @public
     * @example
     * ```
     * $accessPoint = App::$dataAccessPoints->Get('main');
     * $query = $accessPoint->ForQuery('my_field', 'my_table');
     * ```
     */
    public function ForQuery(string $field, string $table): string
    {
        if(!$this->_connected) {
            $this->_connection->Open();
            $this->_connected = true;
        }
        return $this->CreateQuery('CreateFieldForQuery', [$field, $table]);
    }

    /**
     * Creates a query to check for soft deletion in a table.
     *
     * @param string $field The field name.
     * @param string $table The table name.
     * @return string The generated query string for soft deletion check.
     * @public
     * @example
     * ```
     * $accessPoint = App::$dataAccessPoints->Get('main');
     * $query = $accessPoint->SoftDeleteCheck('my_field', 'my_table');
     * ```
     */
    public function SoftDeleteCheck(string $field, string $table): string
    {
        if(!$this->_connected) {
            $this->_connection->Open();
            $this->_connected = true;
        }
        return $this->CreateQuery('CreateSoftDeleteQuery', [$field, $table]);
    }

    /**
     * Processes filters for a storage.
     *
     * @param Storage $storage The storage object.
     * @param string $fullTextSearchTerms The full-text search terms.
     * @param array $filters The filters to apply.
     * @param string $sortField The field to sort by.
     * @param string $sortOrder The sort order (ASC or DESC).
     * @param bool $useAsManageFilter Whether to use as a manage filter.
     * @return array The processed filters as an array.
     * @public
     * @example
     * ```
     * $accessPoint = App::$dataAccessPoints->Get('main');
     * $filters = $accessPoint->ProcessFilters($storage, 'search terms', ['field1' => 'value1'], 'field2', 'ASC', true);
     * ```
     */
    public function ProcessFilters(Storage $storage, string $fullTextSearchTerms, array $filters, string $sortField, string $sortOrder, bool $useAsManageFilter = true): array
    {
        if(!$this->_connected) {
            $this->_connection->Open();
            $this->_connected = true;
        }
        return $this->CreateQuery('ProcessFilters', [$storage, $fullTextSearchTerms, $filters, $sortField, $sortOrder, $useAsManageFilter]);
    }

    /**
     * Processes mutation data for a row.
     *
     * @param mixed $row The row data to process.
     * @param string $mutationType The type of mutation (e.g., insert, update, delete).
     * @return array The processed mutation data as an array.
     * @public
     * @example
     * ```
     * $accessPoint = App::$dataAccessPoints->Get('main');
     * $mutationData = $accessPoint->ProcessMutationData($row, 'insert');
     * ```
     */
    public function ProcessMutationData(mixed $row, string $mutationType): array
    {
        if(!$this->_connected) {
            $this->_connection->Open();
            $this->_connected = true;
        }
        return $this->CreateQuery('ProcessMutationData', [$row, $mutationType]);
    }

    /**
     * Migrates storage in databases by storage configuration.
     *
     * @param Logger $logger The logger instance for logging migration progress.
     * @param string $storage The source storage name.
     * @param array $xstorage The target storage configuration.
     * @return void
     * @public
     * @example
     * ```
     * $accessPoint = App::$dataAccessPoints->Get('main');
     * $accessPoint->Migrate($logger, 'source_storage', ['target_storage' => 'target_config']);
     * ```
     */
    public function Migrate(Logger $logger, string $storage, array $xstorage): void
    {
        if(!$this->_connected) {
            $this->_connection->Open();
            $this->_connected = true;
        }
        $this->ExecuteCommand('Migrate', $logger, $storage, $xstorage);
    }

}
