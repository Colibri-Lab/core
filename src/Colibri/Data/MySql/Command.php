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

namespace Colibri\Data\MySql;

use Colibri\Data\SqlClient\Command as SqlCommand;
use Colibri\Data\MySql\Exception as MySqlException;
use Colibri\Data\SqlClient\IDataReader;
use Colibri\Utils\Debug;
use mysqli_stmt;

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
     * @return mysqli_stmt
     * @testFunction testCommand_prepareStatement
     */
    private function _prepareStatement(string $query): mysqli_stmt
    {

        if (!$this->_params) {
            throw new MySqlException('no params', 0);
        }

        $res = preg_match_all('/\[\[([^\]]+)\]\]/', $query, $matches);
        if ($res == 0) {
            throw new MySqlException('no params', 0);
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
                $types = array_merge($types, array_fill(0, count($this->_params[$matching[0]]), $typesAliases[$matching[1]]));
                $values = array_merge($values, $this->_params[$matching[0]]);
                $query = str_replace('[[' . $match . ']]', implode(',', array_fill(0, count($this->_params[$matching[0]]), '?')), $query);
            }
        }

        $stmt = mysqli_prepare($this->_connection->resource, $query);
        if (!$stmt) {
            throw new MySqlException(mysqli_error($this->_connection->resource), mysqli_errno($this->_connection->resource));
        }

        // чертов бред!
        // ! поменять когда будет php7
        $params = [&$stmt, implode('', $types)];
        for ($i = 0; $i < count($values); $i++) {
            $params[] = &$values[$i];
        }

        call_user_func_array('mysqli_stmt_bind_param', $params);

        return $stmt;
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
        mysqli_select_db($this->_connection->resource, $this->_connection->database);

        // если нужно посчитать количество результатов
        $affected = null;
        if ($this->page > 0 && $info) {

            // выполняем запрос для получения количества результатов без limit-ов
            if ($this->_params) {
                $stmt = $this->_prepareStatement('select count(*) as affected from (' . $this->query . ') tbl');
                mysqli_stmt_execute($stmt);
                $ares = mysqli_stmt_get_result($stmt);
                $affected = mysqli_fetch_object($ares)->affected;
            } else {
                $ares = mysqli_query($this->_connection->resource, 'select count(*) as affected from (' . $this->query . ') tbl');
                if (mysqli_num_rows($ares) > 0) {
                    $affected = mysqli_fetch_object($ares)->affected;
                }
            }
        }

        // добавляем к тексту запроса limit-ы
        $preparedQuery = $this->PrepareQueryString();


        // выполняем запрос
        if ($this->_params) {
            $stmt = $this->_prepareStatement($preparedQuery);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
        } else {
            $res = mysqli_query($this->connection->resource, $preparedQuery);
            if (!($res instanceof \mysqli_result)) {
                throw new MySqlException(mysqli_error($this->_connection->resource) . ' query: ' . $preparedQuery, mysqli_errno($this->_connection->resource));
            }
        }
        return new DataReader($res, $affected, $preparedQuery);

    }

    /**
     * Выполняет запрос и возвращает NonQueryInfo
     *
     * @return NonQueryInfo
     * @testFunction testCommandExecuteNonQuery
     */
    public function ExecuteNonQuery(): NonQueryInfo
    {
        mysqli_select_db($this->_connection->resource, $this->_connection->database);

        if ($this->_params) {
            $stmt = $this->_prepareStatement($this->query);
            mysqli_stmt_execute($stmt);
            return new NonQueryInfo($this->type, mysqli_stmt_insert_id($stmt), mysqli_stmt_affected_rows($stmt), mysqli_stmt_error($stmt), $this->query);
        } else {
            mysqli_query($this->_connection->resource, $this->query);
            return new NonQueryInfo($this->type, mysqli_insert_id($this->connection->resource), mysqli_affected_rows($this->connection->resource), mysqli_error($this->connection->resource), $this->query);
        }
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
