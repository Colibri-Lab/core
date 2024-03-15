<?php

/**
 * Driver for PostgreSql
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Utils\Config
 * @version 1.0.0
 *
 */

namespace Colibri\Data\PgSql;

use Colibri\Data\SqlClient\Command as SqlCommand;
use Colibri\Data\PgSql\Exception as PgSqlException;
use Colibri\Data\SqlClient\IDataReader;
use Colibri\Data\SqlClient\QueryInfo;
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
     * Executes a query and returns a QueryInfo object.
     *
     * @param string|null $returning (Optional) The column name to return after executing the query.
     * @return QueryInfo A QueryInfo object containing information about the executed query.
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
     * Prepares the query string by adding pagination and other necessary modifications for the specific driver.
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
}
