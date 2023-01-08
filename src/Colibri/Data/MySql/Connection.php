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

use Colibri\Data\SqlClient\IConnection;
use Colibri\Data\MySql\Exception as MySqlException;

/**
 * Класс подключения к базе данных MySql
 * 
 * @property-read resource $resource
 * @property-read resource $raw
 * @property-read resource $connection
 * @property-read bool $isAlive
 * 
 * @testFunction testConnection
 */
final class Connection implements IConnection
{
    private $_connectioninfo = null;

    /** @var \mysqli */
    private $_resource = null;

    /**
     * Создает обьект
     *
     * @param string $host
     * @param string $port
     * @param string $user
     * @param string $password
     * @param bool $persistent
     * @param string $database
     */
    public function __construct(string $host, string $port, string $user, string $password, bool $persistent = false, string $database = null)
    {
        $this->_connectioninfo = (object) [
            'host' => $host,
            'port' => $port,
            'user' => $user,
            'password' => $password,
            'persistent' => $persistent,
            'database' => $database
        ];
    }

    /**
     * Открывает подключения
     *
     * @return bool
     * @testFunction testConnectionOpen
     */
    public function Open(): bool
    {

        if (is_null($this->_connectioninfo)) {
            throw new MySqlException('You must provide a connection info object while creating a connection.');
        }

        try {
            $this->_resource = mysqli_connect(($this->_connectioninfo->persistent ? 'p:' : '') . $this->_connectioninfo->host . ($this->_connectioninfo->port ? ':' . $this->_connectioninfo->port : ''), $this->_connectioninfo->user, $this->_connectioninfo->password);
            if (!$this->_resource) {
                throw new MySqlException('Connection: ' . $this->_connectioninfo->host . ' ' . $this->_connectioninfo->port . ' ' . $this->_connectioninfo->user . ': ' . mysqli_connect_error());
            }
        } catch (\Throwable $e) {
            throw new MySqlException('Connection: ' . $this->_connectioninfo->host . ' ' . $this->_connectioninfo->port . ' ' . $this->_connectioninfo->user . ': ' . $e->getMessage(), $e->getCode(), $e);
        }

        if (!empty($this->_connectioninfo->database) && !mysqli_select_db($this->_resource, $this->_connectioninfo->database)) {
            throw new MySqlException(mysqli_error($this->_resource));
        }

        mysqli_query($this->_resource, 'set names utf8');

        return true;
    }

    /**
     * Переорктывает подключение
     *
     * @return bool
     * @testFunction testConnectionReopen
     */
    public function Reopen(): bool
    {
        return $this->Open();
    }

    /**
     * Закрывает подключение
     *
     * @return void
     * @testFunction testConnectionClose
     */
    public function Close(): void
    {
        if (is_resource($this->_resource)) {
            mysqli_close($this->_resource);
        }
    }

    /**
     * Геттер
     *
     * @param string $property
     * @return mixed
     * @testFunction testConnection__get
     */
    public function __get(string $property): mixed
    {
        switch (strtolower($property)) {
            case "resource":
            case "raw":
            case "connection":
                return $this->_resource;
            case "isAlive":
                return mysqli_ping($this->_resource);
            case 'database':
                return $this->_connectioninfo->database;
            default:
                return null;
        }
    }
}