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
use Colibri\Data\SqlClient\IConnection;
use Colibri\Data\SqlClient\IDataReader;
use Colibri\Data\SqlClient\QueryInfo;
use Colibri\Utils\Debug;
use DateTime;

/**
 * Access Point
 *
 * ```
 * Example
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
 * @property-read string $name
 * @property-read IConnection $connection
 * @property-read object $point
 *
 */
class DataAccessPoint
{
    /** Execute the query and return a Reader */
    public const QueryTypeReader = 'reader';

    /** Execute the query and return a Reader, but without counting the total number of rows. */
    public const QueryTypeBigData = 'bigdata';

    /** Execute a query that does not involve reading data. */
    public const QueryTypeNonInfo = 'noninfo';

    /** Readonly transation */
    public const TransationReadonly = 'readonly';

    /** ReadWrite transaction */
    public const TransationReadWrite = 'readwrite';

    /**
     * Connection properties
     *
     * @var object
     */
    private object $_accessPointData;

    /**
     * Connection object
     *
     * @var IConnection
     */
    private IConnection $_connection;

    /**
     * Constructor
     *
     * @param object $accessPointData The access point data object.
     */
    public function __construct($accessPointData)
    {

        $this->_accessPointData = $accessPointData;

        $connectionClassObject = $this->_accessPointData->driver->connection;

        $this->_connection = new $connectionClassObject(
            $this->_accessPointData->host,
            $this->_accessPointData->port,
            $this->_accessPointData->user,
            $this->_accessPointData->password,
            $this->_accessPointData->persistent,
            $this->_accessPointData->database
        );
        $this->_connection->Open();
    }

    /**
     * Magic get method
     *
     * @param string $property property connection,point or table name
     * @return mixed
     */
    public function __get($property)
    {
        if ($property == 'connection') {
            return $this->_connection;
        } elseif ($property == 'point') {
            return $this->_accessPointData;
        } else {
            return $this->Query('select * from ' . $property);
        }
    }

    /**
     * Executes a query in the access point.
     *
     * ```
     * ! Attention
     * ! To execute a query with parameters, do the following:
     * ! 1. Parameters are passed in double square brackets [[param:type]], where type can be integer, double, string, or blob.
     * ! 2. Parameters are passed in an associative array or as an object.
     * ! For example: select * from test where id=[[id:integer]] and stringfield like [[likeparam:string]]
     * ! The actual query with parameters ['id' => '1', 'likeparam' => '%brbrbr%'] will be:
     * ! select * from test where id=1 and stringfield like '%brbrbr%'
     * ! Queries can be put into a collection and executed with different parameters.
     * ```
     * 
     * @param string $query The query string.
     * @param object|array $commandParams [
     *                          page, pagesize, params, type = bigdata|noninfo|reader (default reader),
     *                          returning = ''
     *                     ]
     * @return IDataReader|QueryInfo|null Returns an IDataReader object, a QueryInfo object, or null.
     */
    public function Query($query, $commandParams = []): IDataReader|QueryInfo
    {
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
                    App::$log->debug('Query: ' . $delay . ' ms: ' .
                        str_replace("\r", " ", str_replace("\n", " ", $query)) .
                        ' (' . $cmd->page . ', ' . $cmd->pagesize . ') - ' .
                        ', Type: ' . $commandParams->type);
                }
                if(in_array('params', $logSetting)) {
                    App::$log->debug(Debug::ROut($commandParams));
                }
                if(in_array('return', $logSetting)) {
                    App::$log->debug(Debug::Rout($return));
                }
                App::$log->debug('--------');
            }

        }

        return $return;

    }

    /**
     * Inserts a new row.
     *
     * @param string $table The name of the table.
     * @param array $row The row to be inserted.
     * @param string $returning The name of the field whose value needs to be returned. (For MySQL, this can be omitted, and the value of the identity field will be returned.)
     * @return QueryInfo
     */
    public function Insert(string $table, array $row = [], string $returning = '', ?array $params = null): QueryInfo
    {
        $queryParams = ['type' => self::QueryTypeNonInfo, 'returning' => $returning];
        if (!is_null($params)) {
            $queryParams['params'] = $params;
        }
        $querybuilderClassObject = $this->_accessPointData->driver->querybuilder;
        $queryBuilder = new $querybuilderClassObject();
        return $this->Query($queryBuilder->CreateInsert($table, $row), $queryParams);
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
     */
    public function InsertOrUpdate(
        string $table,
        array $row = [],
        array $exceptFields = [],
        string $returning = '' /* used only in postgres*/
    ): QueryInfo {
        $querybuilderClassObject = $this->_accessPointData->driver->querybuilder;
        $queryBuilder = new $querybuilderClassObject();
        return $this->Query($queryBuilder->CreateInsertOrUpdate(
            $table,
            $row,
            $exceptFields
        ), ['type' => self::QueryTypeNonInfo, 'returning' => $returning]);
    }

    /**
     * Inserts multiple rows at once.
     *
     * @param string $table The table.
     * @param array $rows The rows to be inserted.
     * @return QueryInfo
     */
    public function InsertBatch(string $table, array $rows = []): QueryInfo
    {
        $querybuilderClassObject = $this->_accessPointData->driver->querybuilder;
        $queryBuilder = new $querybuilderClassObject();
        return $this->Query($queryBuilder->CreateBatchInsert($table, $rows), ['type' => self::QueryTypeNonInfo]);
    }

    /**
     * Updates a row.
     *
     * @param string $table The table.
     * @param array $row The row to be updated.
     * @param string $condition The update condition.
     * @return QueryInfo|null
     */
    public function Update(string $table, array $row, string $condition, ?array $params = null): QueryInfo
    {
        $queryParams = ['type' => self::QueryTypeNonInfo];
        if (!is_null($params)) {
            $queryParams['params'] = $params;
        }
        $querybuilderClassObject = $this->_accessPointData->driver->querybuilder;
        $queryBuilder = new $querybuilderClassObject();
        return $this->Query($queryBuilder->CreateUpdate($table, $condition, $row), $queryParams);
    }

    /**
     * Deletes a row based on criteria.
     *
     * @param string $table The table.
     * @param string $condition The condition.
     * @return QueryInfo
     */
    public function Delete(string $table, string $condition = ''): QueryInfo
    {
        $querybuilderClassObject = $this->_accessPointData->driver->querybuilder;
        $queryBuilder = new $querybuilderClassObject();
        return $this->Query($queryBuilder->CreateDelete($table, $condition), ['type' => self::QueryTypeNonInfo]);
    }

    /**
     * Returns a list of tables in the database.
     *
     * @return IDataReader|null Returns an IDataReader object or null.
     */    
    public function Tables(): IDataReader|QueryInfo
    {
        $querybuilderClassObject = $this->_accessPointData->driver->querybuilder;
        $queryBuilder = new $querybuilderClassObject();
        return $this->Query($queryBuilder->CreateShowTables(), ['type' => self::QueryTypeReader]);
    }

    /**
     * Starts a transaction.
     * @return void
     */
    public function Begin(?string $type = null): QueryInfo
    {
        $querybuilderClassObject = $this->_accessPointData->driver->querybuilder;
        $queryBuilder = new $querybuilderClassObject();
        return $this->Query($queryBuilder->CreateBegin($type), ['type' => DataAccessPoint::QueryTypeNonInfo]);
    }

    /**
     * Commits the transaction.
     * @return void
     */
    public function Commit(): QueryInfo
    {
        $querybuilderClassObject = $this->_accessPointData->driver->querybuilder;
        $queryBuilder = new $querybuilderClassObject();
        return $this->Query($queryBuilder->CreateCommit(), ['type' => DataAccessPoint::QueryTypeNonInfo]);
    }

    /**
     * Rolls back the transaction.
     * @return void
     */
    public function Rollback(): QueryInfo
    {
        $querybuilderClassObject = $this->_accessPointData->driver->querybuilder;
        $queryBuilder = new $querybuilderClassObject();
        return $this->Query($queryBuilder->CreateRollback(), ['type' => DataAccessPoint::QueryTypeNonInfo]);
    }

    public function Reopen() 
    {
        $this->_connection->Reopen();
    }

}
