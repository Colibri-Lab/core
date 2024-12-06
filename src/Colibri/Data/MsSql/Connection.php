<?php


/**
 * MsSql
 *
 * @author Vahan P. Grigoryan <vahan.grigoryan@gmail.com>
 * @copyright 2019 ColibriLab
 * @package Colibri\Data\MsSql
 */
namespace Colibri\Data\MsSql;

use Colibri\Data\SqlClient\IConnection;
use Colibri\Data\MsSql\Exception as MsSqlException;

/**
 * Class for connecting to the MsSql database.
 *
 * This class provides methods for establishing and managing connections to a MsSql database.
 *
 * @property-read resource $resource The MsSql connection resource.
 * @property-read resource $raw The raw MsSql connection resource.
 * @property-read resource $connection Alias for $resource.
 * @property-read bool $isAlive Indicates whether the connection to the MsSql server is alive.
 *
 */
final class Connection implements IConnection
{
    /**
     * @var object|null Connection information object containing host, port, user, password, and database.
     */
    private $_connectioninfo = null;

    /**
     * @var \MsSqli|null The MsSql connection resource.
     */
    private $_resource = null;

    /**
     * Connection constructor.
     *
     * Initializes a new Connection object with the provided connection information.
     *
     * @param string $host The hostname or IP address of the MsSql server.
     * @param string $port The port number of the MsSql server.
     * @param string $user The MsSql username.
     * @param string $password The MsSql password.
     * @param bool $persistent Whether to use a persistent connection (true) or not (false).
     * @param string|null $database (Optional) The name of the default database to connect to.
     */
    public function __construct(string $host, string $port, string $user, string $password, ?bool $persistent = false, ?string $database = null)
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

    public static function FromConnectionInfo(object|array $connectionInfo): static
    {
        $connectionInfo = (object)$connectionInfo;
        return new static(
            $connectionInfo->host,
            $connectionInfo->port,
            $connectionInfo->user,
            $connectionInfo->password,
            $connectionInfo?->persistent ?? false,
            $connectionInfo?->database ?? null
        );
    }

    /**
     * Opens a connection to the MsSql database server.
     *
     * @return bool Returns true if the connection was successful; otherwise, false.
     *
     * @throws MsSqlException If an error occurs while establishing the connection.
     *
     */
    public function Open(): bool
    {

        if (is_null($this->_connectioninfo)) {
            throw new MsSqlException('You must provide a connection info object while creating a connection.');
        }

        try {
            $this->_resource = \sqlsrv_connect(
                $this->_connectioninfo->host . ',' . $this->_connectioninfo->port,
                [
                    'Database' => $this->_connectioninfo->database,
                    'UID' => $this->_connectioninfo->user,
                    'PWD' => $this->_connectioninfo->password
                ]
            );
            if (!$this->_resource) {
                throw new MsSqlException(
                    'Connection: ' . $this->_connectioninfo->host . ' ' .
                        $this->_connectioninfo->port . ' ' . $this->_connectioninfo->user . ': ' .
                        implode(',', \sqlsrv_errors())
                );
            }
        } catch (\Throwable $e) {
            throw new MsSqlException(
                'Connection: ' . $this->_connectioninfo->host . ' ' .
                $this->_connectioninfo->port . ' ' . $this->_connectioninfo->user . ': ' . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }

        // sqlsrv_query($this->_resource, 'SET NAMES utf8mb4 COLLATE utf8mb4_general_ci');

        return true;
    }

    /**
     * Reopens the MsSql database connection.
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
     * Closes the MsSql database connection.
     *
     * @return void
     *
     */
    public function Close(): void
    {
        if (is_resource($this->_resource)) {
            \sqlsrv_close($this->_resource);
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
                return '`';
            default:
                return null;
        }
    }

    public function Ping(): bool {
        return sqlsrv_query($this->_resource, 'SELECT true') !== false;
    }

    public static function AllowedTypes(): array
    {
        return [
            'bool' => ['length' => false, 'generic' => 'bool'],
            'int' => ['length' => true, 'generic' => 'int'],
            'bigint' => ['length' => false, 'generic' => 'int'],
            'float' => ['length' => true, 'generic' => 'float'],
            'double' => ['length' => true, 'generic' => 'float'],
            'date' => ['length' => false, 'generic' => 'DateField'],
            'datetime' => ['length' => false, 'generic' => 'DateTimeField'],
            'varchar' => ['length' => true, 'generic' => 'string'],
            'text' => ['length' => false, 'generic' => 'string'],
            'longtext' => ['length' => false, 'generic' => 'string'],
            'mediumtext' => ['length' => false, 'generic' => 'string'],
            'tinytext' => ['length' => true, 'generic' => 'string'],
            'enum' => ['length' => false, 'generic' => 'ValueField'],
            'json' => ['length' => false, 'generic' => ['Object' => 'ObjectField', 'Array' => 'ArrayField']]
        ];
    }
}
