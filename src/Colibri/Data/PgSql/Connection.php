<?php

/**
 * PgSql
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\PgSql
 */

namespace Colibri\Data\PgSql;

use Colibri\Data\SqlClient\IConnection;
use Colibri\Data\PgSql\Exception as PgSqlException;
use PgSql\Connection as PgSqlConnection;

/**
 * Class for connecting to the PostgreSql database.
 *
 * This class provides methods for establishing and managing connections to a PostgreSql database.
 *
 * @property-read resource $resource The PostgreSql connection resource.
 * @property-read resource $raw The raw PostgreSql connection resource.
 * @property-read resource $connection Alias for $resource.
 * @property-read bool $isAlive Indicates whether the connection to the PostgreSql server is alive.
 *
 */
final class Connection implements IConnection
{
    /**
     * @var object|null Connection information object containing host, port, user, password, and database.
     */
    private $_connectioninfo = null;

    /**
     * @var PgSqlConnection|null The PostgreSql connection resource.
     */
    private $_resource = null;

    /**
     * Connection constructor.
     *
     * Initializes a new Connection object with the provided connection information.
     *
     * @param string $host The hostname or IP address of the PostgreSql server.
     * @param string $port The port number of the PostgreSql server.
     * @param string $user The PostgreSql username.
     * @param string $password The PostgreSql password.
     * @param bool $persistent Whether to use a persistent connection (true) or not (false).
     * @param string|null $database (Optional) The name of the default database to connect to.
     */
    public function __construct(
        string $host,
        string $port,
        string $user,
        string $password,
        bool $persistent = false,
        string $database = null
    ) {
        $this->_connectioninfo = (object) [
            'host' => $host,
            'port' => $port,
            'user' => $user,
            'password' => $password,
            'persistent' => $persistent,
            'database' => $database
        ];
    }

    public static function FromConnectionInfo(object|array $connectionInfo): static
    {
        $connectionInfo = (object)$connectionInfo;
        return new static(
            $connectionInfo->host,
            $connectionInfo->port,
            $connectionInfo->user,
            $connectionInfo->password,
            $connectionInfo->persistent,
            $connectionInfo->database
        );
    }

    /**
     * Opens a connection to the PostgreSql database server.
     *
     * @return bool Returns true if the connection was successful; otherwise, false.
     *
     * @throws PgSqlException If an error occurs while establishing the connection.
     *
     */
    public function Open(): bool
    {

        if (is_null($this->_connectioninfo)) {
            throw new PgSqlException('You must provide a connection info object while creating a connection.');
        }

        try {
            $this->_resource = pg_pconnect(
                'host='.$this->_connectioninfo->host.
                ' port='.$this->_connectioninfo->port.
                ' dbname='.$this->_connectioninfo->database.
                ' user='.$this->_connectioninfo->user.
                ' password='.$this->_connectioninfo->password,
                PGSQL_CONNECT_FORCE_NEW
            );
            if (!$this->_resource) {
                throw new PgSqlException(
                    'Connection: ' . $this->_connectioninfo->host . ' ' .
                    $this->_connectioninfo->port . ' ' .
                    $this->_connectioninfo->user . ': ' .
                    pg_last_error()
                );
            }
        } catch (\Throwable $e) {
            throw new PgSqlException(
                'Connection: ' . $this->_connectioninfo->host . ' ' .
                $this->_connectioninfo->port . ' ' .
                $this->_connectioninfo->user . ': ' .
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }

        // if (!empty($this->_connectioninfo->database) && !PostgreSqli_select_db($this->_resource, $this->_connectioninfo->database)) {
        //     throw new PgSqlException(pg_last_error($this->_resource));
        // }

        // pg_query($this->_resource, 'set names utf8');

        return true;
    }

    /**
     * Reopens the PostgreSql database connection.
     *
     * This method is an alias for Open().
     *
     * @return bool Returns true if the connection was successfully reopened; otherwise, false.
     *
     */
    public function Reopen(): bool
    {
        return $this->Open();
    }

    /**
     * Closes the PostgreSql database connection.
     *
     * @return void
     *
     */
    public function Close(): void
    {
        if (is_resource($this->_resource)) {
            pg_close($this->_resource);
        }
    }

    /**
     * Magic getter method.
     *
     * Allows access to read-only properties such as $resource, $raw, $connection, and $isAlive.
     *
     * @param string $property The name of the property to retrieve.
     * @return mixed Returns the value of the requested property, or null if the property does not exist.
     *
     */
    public function __get(string $property): mixed
    {
        switch (strtolower($property)) {
            case "resource":
            case "raw":
            case "connection":
                return $this->_resource;
            case "isAlive":
                return $this->Ping();
            case 'database':
                return $this->_connectioninfo->database;
            case 'host':
                return $this->_connectioninfo->host;
            case 'port':
                return $this->_connectioninfo->port;
            case 'symbol':
                return '"';
            default:
                return null;
        }
    }

    public function Ping(): bool
    {
        return pg_ping($this->_resource);
    }
    
    public static function AllowedTypes(): array
    {
        return [
            'bool' => ['length' => false, 'generic' => 'bool', 'component' => 'Colibri.UI.Forms.Checkbox'],
            'int' => ['length' => true, 'generic' => 'int', 'component' => 'Colibri.UI.Forms.Number'],
            'bigint' => ['length' => false, 'generic' => 'int', 'component' => 'Colibri.UI.Forms.Number'],
            'float' => ['length' => true, 'generic' => 'float', 'component' => 'Colibri.UI.Forms.Number'],
            'double' => ['length' => true, 'generic' => 'float', 'component' => 'Colibri.UI.Forms.Number'],
            'date' => ['length' => false, 'generic' => 'DateField', 'component' => 'Colibri.UI.Forms.Date'],
            'datetime' => ['length' => false, 'generic' => 'DateTimeField', 'component' => 'Colibri.UI.Forms.DateTime'],
            'varchar' => ['length' => true, 'generic' => 'string', 'component' => 'Colibri.UI.Forms.Text'],
            'text' => ['length' => false, 'generic' => 'string', 'component' => 'Colibri.UI.Forms.TextArea'],
            'longtext' => ['length' => false, 'generic' => 'string', 'component' => 'Colibri.UI.Forms.TextArea'],
            'mediumtext' => ['length' => false, 'generic' => 'string', 'component' => 'Colibri.UI.Forms.TextArea'],
            'tinytext' => ['length' => true, 'generic' => 'string', 'component' => 'Colibri.UI.Forms.TextArea'],
            'enum' => ['length' => false, 'generic' => 'ValueField', 'component' => 'Colibri.UI.Forms.Select'],
            'json' => ['length' => false, 'generic' => ['Colibri.UI.Forms.Object' => 'ObjectField', 'Colibri.UI.Forms.Array' => 'ArrayField'], 'component' => 'Colibri.UI.Forms.Object']
        ];
    }
}
