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
}
