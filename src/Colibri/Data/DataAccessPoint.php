<?php

/**
 * Доступ к базе данных
 *
 * @author Ваган Григорян <vahan.grigoryan@gmail.com>
 * @copyright 2019 Colibri
 * @package Colibri\Utils\Config
 * @version 1.0.0
 *
 */

namespace Colibri\Data;

use Colibri\App;
use Colibri\Data\SqlClient\IConnection;
use Colibri\Data\SqlClient\Command;
use Colibri\Data\SqlClient\IDataReader;
use Colibri\Data\SqlClient\IQueryBuilder;
use Colibri\Data\SqlClient\QueryInfo;
use Colibri\Utils\Debug;
use DateTime;
use stdClass;

/**
 * Точка доступа
 * 
 * <code>
 * 
 *      $accessPoint = App::$dataAccessPoints->Get('main');
 *      
 *      # Получение данных по запросу
 * 
 *      class Queries {
 *          const TestSelectQuery = 'select * from test where id=[[id:integer]] and text=[[text:string]] and dbl=[[dbl::double]]';
 *      }
 *      $reader = $accessPoint->Query(Queries::TestSelectQuery, ['page' => 1, 'pagesize' => 10, 'params' => ['id' => 1, 'text' => 'adfadf', 'dbl' => 1.1]]);
 *      while($result = $reader->Read()) {
 *          print_r($result); // обьект
 *      }
 *      
 *      # или без параметров
 * 
 *      $reader = $accessPoint->Query('select * from test where id=\'2\' and text=\'adfasdfasdf\' and dbl=\'1.1\'', ['page' => 1, 'pagesize' => 10]);
 *      while($result = $reader->Read()) {
 *          print_r($result); // обьект
 *      }
 *
 *      $accessPoint->Query('BEGIN');
 * 
 *      # если необходимо выполнить запрос insert, update или delete 
 *      $nonQueryInfo = $accessPoint->Query('delete from test where id=1', ['type' => DataAccessPoint::QueryTypeNonInfo]);
 *      
 *      # если необходимо выполнить запрос с большим количеством данных, например для запросов с автоподкачкой 
 *      $reader = $accessPoint->Query('select * from test', ['page' => 1, 'pagesize' => 100, 'type' => DataAccessPoint::QueryTypeBigData]);
 *      
 *      # ввод данных
 *      $nonQueryInfo = $accessPoint->Insert('test', ['text' => 'адфасдфасдфасдф', 'dbl' => 1.1], 'id'); # только для postgresql
 *      # возвращается класс QueryInfo, для postgres необходимо передать дополнительный параметр returning - название поля, которое нужно вернуть
 * 
 *      # обновление данных     
 *      $returnsBool = $accessPoint->Update('test', ['text' => 'adfasdfasdf', 'dbl' => 1.2], 'id=1');
 *      # возвращает true если обновление прошло успешно
 * 
 *      # ввод с обновлением данных, если есть дубликат по identity полю или sequence для postgresql
 *      $nonQueryInfo = $accessPoint->InsertOrUpdate('test', ['id' => 1, 'text' => 'adfadsfads', 'dbl' => 1.1], ['id', 'text'], 'id'); 
 *      # поле returning нужно только для postgresql
 *      # возвращается класс QueryInfo, для postgres необходимо передать дополнительный параметр returning - название поля, которое нужно вернуть
 * 
 *      # ввод данны пачкой
 *      $nonQueryInfo = $accessPoint->InsertBatch('test', [ ['text' => 'adsfasdf', 'dbl' => 1.0], ['text' => 'adsfasdf', 'dbl' => 1.1] ]);
 * 
 *      $accessPoint->Query('COMMIT');
 * 
 *      # удаление данных
 *      $returnsBool = $accessPoint->Delete('test', 'id=1');
 *      # возвращает true если удаление прошло успешно, нужно учесть, что если не передать параметр condition то будет выполнено truncate table test
 * 
 *      # получение списка таблиц
 *      $tablesReader = $accessPoint->Tables();
 *      # возвращает IDataReader 
 * 
 * </code>
 *
 * @property-read string $name
 * @property-read IConnection $connection 
 *
 * @testFunction testDataAccessPoint
 */
class DataAccessPoint
{

    /** Выполнить запрос и вернуть Reader */
    const QueryTypeReader = 'reader';
    /** Выполнить запрос и вернуть Reader, но без подсчета общего количества строк */
    const QueryTypeBigData = 'bigdata';
    /** Выполнить запрос, который не подразумевает чтения данных */
    const QueryTypeNonInfo = 'noninfo';

    /**
     * Информация о подключении
     *
     * @var stdClass
     */
    private $_accessPointData;

    /**
     * Подключение
     *
     * @var IConnection
     */
    private $_connection;

    /**
     * Конструктор
     *
     * @param stdClass $accessPointData
     */
    public function __construct($accessPointData)
    {

        $this->_accessPointData = $accessPointData;

        // получаем название класса Connection-а, должен быть классом интерфейса IDataConnection
        $connectionClassObject = $this->_accessPointData->driver->connection;

        $this->_connection = new $connectionClassObject($this->_accessPointData->host, $this->_accessPointData->port, $this->_accessPointData->user, $this->_accessPointData->password, $this->_accessPointData->persistent, $this->_accessPointData->database);
        $this->_connection->Open();
    }

    /**
     * Геттер
     *
     * @param string $property
     * @return mixed
     */
    public function __get($property)
    {
        if ($property == 'connection') {
            return $this->_connection;
        } else if ($property == 'point') {
            return $this->_accessPointData;
        } else {
            return $this->Query('select * from ' . $property);
        }
    }

    /**
     * Выполняет запрос в точку доступа
     *
     * ! Внимание
     * ! Для выполнения зарпоса с параметрами необходимо сделать следующее:
     * ! 1. параметры передаются в двойных квадратных скобках [[param:type]] где type=integer|double|string|blob
     * ! 2. параметры передаются в ассоциативном массиве, либо в виде stdClass
     * ! например: select * from test where id=[[id:integer]] and stringfield like [[likeparam:string]]
     * ! реальный запрос будет следующий с параметрами ['id' => '1', 'likeparam' => '%brbrbr%']: select * from test where id=1 and stringfield like '%brbrbr%'
     * ! запросы можно засунуть в коллекцию и выполнять с разными параметрами 
     * 
     * @param string $query 
     * @param stdClass|array $commandParams [page, pagesize, params, type = bigdata|noninfo|reader (default reader), returning = '']
     * @return IDataReader|QueryInfo|null
     * @testFunction testDataAccessPointQuery
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

        // если не передали type то выставляем в bigquery чтобы лишнего не запрашивать
        if (!isset($commandParams->type)) {
            $commandParams->type = self::QueryTypeBigData;
        }

        $queryStartTime = new DateTime();

        if ($this->_accessPointData->logqueries ?? false) {
            App::$log->debug('Query: ' . $commandParams->type . ', Text: ' . $query . ', Limits: ' . $cmd->page . ' - ' . $cmd->pagesize);
            App::$log->debug(Debug::ROut($commandParams));
        }

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

        } catch (DataAccessPointsException $e) {
            $return = new QueryInfo($cmd->type, 0, 0, $e->getMessage(), $cmd->query);
        }

        if ($this->_accessPointData->logqueries ?? false) {
            $diff = $queryStartTime->diff(new DateTime());
            App::$log->debug('QueryResult: time delta ' . ($diff->format('%F') / 1000));
            App::$log->debug(Debug::Rout($return));
        }

        return $return;

    }

    /**
     * Вводит новую строку
     *
     * @param string $table название таблицы
     * @param array $row вводимая строка
     * @param string $returning название поля, значение которого необходимо вернуть (в случае с MySql можно опустить, будет возвращено значения поля identity)
     * @return QueryInfo
     * @testFunction testDataAccessPointInsert
     */
    public function Insert($table, $row = array(), $returning = ''): QueryInfo
    {
        $querybuilderClassObject = $this->_accessPointData->driver->querybuilder;
        $queryBuilder = new $querybuilderClassObject();
        return $this->Query($queryBuilder->CreateInsert($table, $row), ['type' => self::QueryTypeNonInfo, 'returning' => $returning]);
    }

    /**
     * Вводит новую строку или обновляет старую, если совпали индексные поля
     * Отличный способ не задумываться над тем есть ли строка в базе данных или нет
     * Работает медленнее чем обычный ввод данных, поэтому использовать с осмотрительностью
     *
     * @param string $table таблица
     * @param array $row вводимая строка
     * @param array $exceptFields какие поля исключить из обновления в случае, если строка по идексным полям существует
     * @param string $returning название название поля, значение которого необходимо вернуть (в случае с MySql можно опустить, будет возвращено значения поля identity)
     * @return QueryInfo
     * @testFunction testDataAccessPointInsertOrUpdate
     */
    public function InsertOrUpdate($table, $row = array(), $exceptFields = array(), $returning = '' /* used only in postgres*/): QueryInfo
    {
        $querybuilderClassObject = $this->_accessPointData->driver->querybuilder;
        $queryBuilder = new $querybuilderClassObject();
        return $this->Query($queryBuilder->CreateInsertOrUpdate($table, $row, $exceptFields), ['type' => self::QueryTypeNonInfo, 'returning' => $returning]);
    }

    /**
     * Вводит кного строк разом
     *
     * @param string $table таблица 
     * @param array $rows вводимые строки
     * @return QueryInfo
     * @testFunction testDataAccessPointInsertBatch
     */
    public function InsertBatch($table, $rows = array()): QueryInfo
    {
        $querybuilderClassObject = $this->_accessPointData->driver->querybuilder;
        $queryBuilder = new $querybuilderClassObject();
        return $this->Query($queryBuilder->CreateBatchInsert($table, $rows), ['type' => self::QueryTypeNonInfo]);
    }

    /**
     * Обновляет строку
     *
     * @param string $table таблица
     * @param array $row обновляемая строка
     * @param string $condition условие обновления
     * @return QueryInfo|null
     * @testFunction testDataAccessPointUpdate
     */
    public function Update($table, $row, $condition): QueryInfo
    {
        $querybuilderClassObject = $this->_accessPointData->driver->querybuilder;
        $queryBuilder = new $querybuilderClassObject();
        return $this->Query($queryBuilder->CreateUpdate($table, $condition, $row), ['type' => self::QueryTypeNonInfo]);
    }

    /**
     * Удалет строку по критериям
     *
     * @param string $table таблица
     * @param string $condition условие
     * @return QueryInfo
     * @testFunction testDataAccessPointDelete
     */
    public function Delete($table, $condition = ''): QueryInfo
    {
        $querybuilderClassObject = $this->_accessPointData->driver->querybuilder;
        $queryBuilder = new $querybuilderClassObject();
        return $this->Query($queryBuilder->CreateDelete($table, $condition), ['type' => self::QueryTypeNonInfo]);
    }

    /**
     * Возвращает список таблиц в базе данных
     *
     * @return IDataReader|null
     * @testFunction testDataAccessPointTables
     */
    public function Tables(): IDataReader|QueryInfo
    {
        $querybuilderClassObject = $this->_accessPointData->driver->querybuilder;
        $queryBuilder = new $querybuilderClassObject();
        return $this->Query($queryBuilder->CreateShowTables(), ['type' => self::QueryTypeReader]);
    }

    /**
     * Создает транзакцию
     * @return void 
     * @testFunction testDataAccessPointBegin
     */
    public function Begin(): QueryInfo
    {
        return $this->Query('start transaction', ['type' => DataAccessPoint::QueryTypeNonInfo]);
    }

    /**
     * Коммитит транзакцию
     * @return void 
     * @testFunction testDataAccessPointCommit
     */
    public function Commit(): QueryInfo
    {
        return $this->Query('commit', ['type' => DataAccessPoint::QueryTypeNonInfo]);
    }

    /**
     * Отменяет транзакцию
     * @return void 
     * @testFunction testDataAccessPointRollback
     */
    public function Rollback(): QueryInfo
    {
        return $this->Query('rollback', ['type' => DataAccessPoint::QueryTypeNonInfo]);
    }
}