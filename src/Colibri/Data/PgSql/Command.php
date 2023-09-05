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

use Colibri\Data\SqlClient\Command as SqlCommand;
use Colibri\Data\PgSql\Exception as PgSqlException;
use Colibri\Data\SqlClient\IDataReader;
use Colibri\Data\SqlClient\QueryInfo;
use Colibri\Utils\Debug;
use PgSql\Result;

/**
 * Класс для выполнения команд в точку доступа
 *
 * @inheritDoc
 *
 * @testFunction testCommand
 */
final class Command extends SqlCommand
{
    /**
     * Подготавливает запрос с параметрами
     * @param string $query
     * @return mixed
     * @testFunction testCommand_prepareStatement
     */
    private function _prepareStatement(string $query): string
    {
        $params = $this->_params;
        preg_replace_callback('/\[\[([^\]]+)\]\]/', function ($match) use ($params) {

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
     * Выполняет запрос и возвращает IDataReader
     *
     * @param boolean $info выполнить ли запрос на получение переменной affected
     * @return IDataReader
     * @testFunction testCommandExecuteReader
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
                ) . ' query: ' . $limitQuery, mysqli_errno($this->_connection->resource));
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
     * Выполняет запрос и возвращает QueryInfo
     *
     * @return QueryInfo
     * @testFunction testCommandExecuteNonQuery
     */
    public function ExecuteNonQuery(?string $returning = null): QueryInfo
    {
        $query = $this->_prepareStatement($this->query);
        $res = pg_query($this->_connection->resource, $query . ($returning ? ' returning ' . $returning : ''));
        $insertId = 0;
        if($returning) {
            $object = pg_fetch_object($res);
            $insertId = $object->$returning;
        }
        return new QueryInfo(
            $this->type,
            $insertId,
            pg_affected_rows($res),
            pg_last_error($this->connection->resource),
            $this->query
        );
    }

    /**
     * Подготавливает строку, добавляет постраничку и все, что необходимо для конкретного драйвера
     *
     * @return string
     * @testFunction testCommandPrepareQueryString
     */
    public function PrepareQueryString(): string
    {
        $query = $this->query;
        if ($this->_page > 0) { //  && strstr($query, "limit") === false
            $query .= ' limit ' . (($this->_page - 1) * $this->_pagesize) . ', ' . $this->_pagesize;
        }
        return $query;
    }
}
